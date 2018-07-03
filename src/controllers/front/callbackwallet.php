<?php


class YandexModuleCallbackWalletModuleFrontController extends ModuleFrontController
{
    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        $this->module->log('info', 'p2p callback init: params = '.json_encode($_POST));
        if($this->checkSignature() && !empty($_POST['label'])) {
            $orderId = $_POST['label'];
            $order   = new Order((int)$orderId);

            if (isset($order)) {
                $history           = new OrderHistory();
                $history->id_order = $order->id;
                $state             = Configuration::get('YA_WALLET_END_STATUS');
                if (empty($state)) {
                    $state = Configuration::get('PS_OS_PAYMENT');
                }
                $history->changeIdOrderState($state, $order->id);
                $history->addWithemail(true);
            }
        } else {
            $this->module->log('info', 'p2p callback error: params = '.json_encode($_POST));
        }
    }

    private function checkSignature()
    {
        if (empty($_POST['sha1_hash'])) {
            return false;
        } else {
            $shaHash = $_POST['sha1_hash'];
        }

        $notificationSecret = Configuration::get('YA_WALLET_PASSWORD');

        $notificationType = isset($_POST['notification_type']) ? $_POST['notification_type'] : '';
        $operationId      = isset($_POST['operation_id']) ? $_POST['operation_id'] : '';
        $amount           = isset($_POST['amount']) ? $_POST['amount'] : '';
        $currency         = isset($_POST['currency']) ? $_POST['currency'] : '';
        $datetime         = isset($_POST['datetime']) ? $_POST['datetime'] : '';
        $sender           = isset($_POST['sender']) ? $_POST['sender'] : '';
        $codepro          = isset($_POST['codepro']) ? $_POST['codepro'] : '';
        $label            = isset($_POST['label']) ? $_POST['label'] : '';

        $data = array(
            $notificationType,
            $operationId,
            $amount,
            $currency,
            $datetime,
            $sender,
            $codepro,
            $notificationSecret,
            $label,
        );

        $dataString = implode('&', $data);
        $signature  = sha1($dataString);

        return $shaHash == $signature;
    }
}