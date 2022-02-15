<?php
/**
 * @author YooMoney <cms@yoomoney.ru>
 * @copyright © 2022 "YooMoney", NBСO LLC
 * @license  https://yoomoney.ru/doc.xml?id=527052
 */
use YooKassa\Model\NotificationEventType;
use YooKassa\Model\PaymentStatus;
use YooKassa\Model\Notification\NotificationFactory;
use YooKassa\Model\RefundStatus;

/**
 * Class YooMoneyModulePaymentKassaModuleFrontController
 *
 * @property yoomoneymodule $module
 */
class YooMoneyModuleNotifyCaptureModuleFrontController extends ModuleFrontController
{
    const REFUND_STATUS_ID = 7;

    /**
     * Обработка входящих уведомлений от Юkassa
     *
     * @return bool|void
     */
    public function postProcess()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            header('HTTP/1.1 405 Method Not Allowed');
            echo '{
                module_version: "'.$this->module->version.'",
                cms_version: "'._PS_VERSION_.'"
            }';
            exit();
        }
        if (!$this->module->active) {
            return;
        }
        $source = Tools::file_get_contents('php://input');
        if (empty($source)) {
            $this->module->log('notice', 'Call capture notification controller without body');
            header('HTTP/1.1 400 Empty notification object');

            return;
        }
        $data = json_decode($source, true);
        if (empty($data)) {
            if (json_last_error() === JSON_ERROR_NONE) {
                $message = 'empty object in body';
            } else {
                $message = 'invalid object in body: '.json_last_error_msg();
            }
            $this->module->log('warning', 'Invalid parameters in capture notification controller - '.$message);
            header('HTTP/1.1 400 Invalid notification object');

            return;
        }

        $kassa = $this->module->getKassaModel();

        try {
            $factory = new NotificationFactory();
            $notificationObject = $factory->factory($data);

            $paymentId = $notificationObject->getObject()->getId();

            if ($notificationObject->getEvent() === NotificationEventType::REFUND_SUCCEEDED) {
                $refundNotifyObj = $notificationObject->getObject();
                $refundKassaObj = $kassa->fetchRefund($refundNotifyObj->getId());
                $paymentId = $refundKassaObj->getPaymentId();
            }

            $paymentModel  = $kassa->getPayment($paymentId);

            $result = false;
            if (
                $notificationObject->getEvent() === NotificationEventType::PAYMENT_SUCCEEDED
                && $paymentModel->getStatus() === PaymentStatus::SUCCEEDED
            ) {
                $kassa->updatePaymentStatus($paymentModel);
                $orderId = $kassa->getOrderIdByPayment($paymentModel);
                $orderStatusId  = $kassa->getSuccessStatusId();
                $history = new OrderHistory();
                $history->id_order = $orderId;
                $history->changeIdOrderState($orderStatusId, $orderId);
                $history->addWithemail(true);
                // обновляем номер транзакции, привязанной к заказу
                $kassa->updateOrderPaymentId($orderId, $paymentModel);
                $result = true;
            }

            if (
                $notificationObject->getEvent() === NotificationEventType::PAYMENT_WAITING_FOR_CAPTURE
                && $paymentModel->getStatus() === PaymentStatus::WAITING_FOR_CAPTURE
            ) {
                $this->module->log('info', 'Notification waiting for capture init.');

                $kassa->updatePaymentStatus($paymentModel);
                $orderId = $kassa->getOrderIdByPayment($paymentModel);

                $history = new OrderHistory();
                $history->id_order = $orderId;
                $history->changeIdOrderState($kassa->getOnHoldStatusId(), $orderId);
                $history->addWithemail(true);
                $kassa->updateOrderPaymentId($orderId, $paymentModel);
                $result = true;
            }

            if (
                $notificationObject->getEvent() === NotificationEventType::PAYMENT_CANCELED
                && $paymentModel->getStatus() === PaymentStatus::CANCELED
            ) {
                $this->module->log('info', 'Notification payment canceled init.');

                $kassa->updatePaymentStatus($paymentModel);
                $orderId = $kassa->getOrderIdByPayment($paymentModel);
                $history = new OrderHistory();
                $history->id_order = $orderId;
                $history->changeIdOrderState($kassa->getCancelStatusId(), $orderId);
                $history->addWithemail(true);
                $kassa->updateOrderPaymentId($orderId, $paymentModel);

                $result = true;
            }

            if (
                $notificationObject->getEvent() === NotificationEventType::REFUND_SUCCEEDED
                && $refundKassaObj->getStatus() === RefundStatus::SUCCEEDED
                && $paymentModel->getStatus() === PaymentStatus::SUCCEEDED
            ) {
                $this->module->log('info', 'Notification payment refund init.');

                $kassa->updatePaymentStatus($paymentModel);
                $orderId = $kassa->getOrderIdByPayment($paymentModel);
                $history = new OrderHistory();
                $history->id_order = $orderId;
                $history->changeIdOrderState(self::REFUND_STATUS_ID, $orderId);
                $history->addWithemail(true);
                $kassa->updateOrderPaymentId($orderId, $paymentModel);

                $result = true;
            }

            if (
                $notificationObject->getEvent() === NotificationEventType::DEAL_CLOSED
                || $notificationObject->getEvent() === NotificationEventType::PAYOUT_CANCELED
                || $notificationObject->getEvent() === NotificationEventType::PAYOUT_SUCCEEDED
            ) {
                $this->module->log(
                    'info',
                    'Notification ' . $notificationObject->getEvent() . ' received.'
                );

                return true;
            }

            if (!$result) {
                header('HTTP/1.1 500 Server error 1');
                exit();
            }

            echo json_encode(array('success' => true));
            exit();

        } catch (\Exception $e) {
            $this->module->log('error', 'Invalid notification object - '.$e->getMessage());
            header('HTTP/1.1 500 Server error: '.$e->getMessage());

            exit();
        }
    }
}
