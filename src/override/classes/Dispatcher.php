<?php
/**
 * Module is prohibited to sales! Violation of this condition leads to the deprivation of the license!
 *
 * @author    YooMoney <cms@yoomoney.ru>
 * @copyright © 2020 "YooMoney", NBСO LLC
 * @license   https://yoomoney.ru/doc.xml?id=527052
 *
 * @category  Front Office Features
 * @package   YooMoney Payment Solution
 */

class Dispatcher extends DispatcherCore
{
    protected function setRequestUri()
    {
        parent::setRequestUri();
        if (Module::isInstalled('yoomoneymodule') && strpos($this->request_uri, 'module/yoomoneymodule/')) {
            $this->request_uri = iconv('windows-1251', 'UTF-8', $this->request_uri);
        }
    }
}
