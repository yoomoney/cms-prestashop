{**
* Module is prohibited to sales! Violation of this condition leads to the deprivation of the license!
*
* @author    Yandex.Money <cms@yamoney.ru>
* @copyright © 2015-2017 NBCO Yandex.Money LLC
* @license   https://money.yandex.ru/doc.xml?id=527052
*
* @category  Front Office Features
* @package   Yandex Payment Solution
*}

<form method="post" action="{$action|escape:'htmlall':'UTF-8'}">
    <label for="ym-billing-fio">{l s='Payer\'s full name' mod='yandexmodule'}</label>
    <input name="ym_billing_fio" id="ym-billing-fio" value="{$fio|escape:'htmlall':'UTF-8'}" />
</form>