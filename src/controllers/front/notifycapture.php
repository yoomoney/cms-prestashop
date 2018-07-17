<?php
/**
 * @author Yandex.Money <cms@yamoney.ru>
 * @copyright © 2015-2017 NBCO Yandex.Money LLC
 * @license  https://money.yandex.ru/doc.xml?id=527052
 */
use YandexCheckout\Model\Notification\NotificationSucceeded;
use YandexCheckout\Model\Notification\NotificationWaitingForCapture;
use YandexCheckout\Model\NotificationEventType;
use YandexCheckout\Model\Payment;
use YandexCheckout\Model\PaymentStatus;

/**
 * Class YandexModulePaymentKassaModuleFrontController
 *
 * @property yandexmodule $module
 */
class YandexModuleNotifyCaptureModuleFrontController extends ModuleFrontController
{
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
        $json = json_decode($source, true);
        if (empty($json)) {
            if (json_last_error() === JSON_ERROR_NONE) {
                $message = 'empty object in body';
            } else {
                $message = 'invalid object in body: '.json_last_error_msg();
            }
            $this->module->log('warning', 'Invalid parameters in capture notification controller - '.$message);
            header('HTTP/1.1 400 Invalid notification object');

            return;
        }
        try {
            if ($json['event'] == NotificationEventType::PAYMENT_WAITING_FOR_CAPTURE) {
                $notification = new NotificationWaitingForCapture($json);
                $this->module->log('info', 'Notification waiting for capture init.');
                $result = $this->module->captureOrHoldPayment($notification->getObject()->getId());
            } else {
                $webhookObject = new NotificationSucceeded($json);
                $this->module->log('info', 'Notification succeeded init.');
                $paymentData   = $webhookObject->getObject();
                $kassa         = $this->module->getKassaModel();
                $paymentModel  = $kassa->getPayment($paymentData->getId());
                if ($paymentModel->status === PaymentStatus::SUCCEEDED) {
                    $kassa->updatePaymentStatus($paymentModel);
                    $orderId           = $kassa->getOrderIdByPayment($paymentModel);
                    $orderStatusId     = $kassa->getSuccessStatusId();
                    $history           = new OrderHistory();
                    $history->id_order = $orderId;
                    $history->changeIdOrderState($orderStatusId, $orderId);
                    $history->addWithemail(true);
                    // обновляем номер транзакции, привязанной к заказу
                    $this->module->getKassaModel()->updateOrderPaymentId($orderId, $paymentData);
                    echo json_encode(array('success' => true));
                    exit();
                } else {
                    $result = false;
                }

            }

        } catch (\Exception $e) {
            $this->module->log('error', 'Invalid notification object - '.$e->getMessage());
            header('HTTP/1.1 500 Server error: '.$e->getMessage());

            return;
        }

        if (!$result) {
            header('HTTP/1.1 500 Server error 1');
            exit();
        }
        echo json_encode(array('success' => $result));
        exit();
    }
}
