<?php
/**
 * @author Yandex.Money <cms@yamoney.ru>
 * @copyright © 2015-2017 NBCO Yandex.Money LLC
 * @license  https://money.yandex.ru/doc.xml?id=527052
 */

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
            $object = new YaMoney\Model\Notification\NotificationWaitingForCapture($json);
        } catch (\Exception $e) {
            $this->module->log('error', 'Invalid notification object - '.$e->getMessage());
            header('HTTP/1.1 500 Server error: '.$e->getMessage());

            return;
        }
        $result = $this->module->capturePayment($object->getObject());
        if (!$result) {
            header('HTTP/1.1 500 Server error 1');
            exit();
        }
        echo json_encode(array('success' => $result));
        exit();
    }
}
