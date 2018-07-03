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

<div class="row">
    <div class="col-xs-12 col-md-6">
        <p class="payment_module">
            <a href="{$link->getModuleLink('yandexmodule', 'redirectwallet', ['type' => 'wallet'])|escape:'quotes':'UTF-8'}" title="{l s='Yandex.Money' mod='yandexmodule'}" class="yandex_money_yandex_money yandex_money_payment">
                {l s='Yandex.Money' mod='yandexmodule'}
            </a>
        </p>
    </div>
</div>
<div class="row">
    <div class="col-xs-12 col-md-6">
        <p class="payment_module">
            <a href="{$link->getModuleLink('yandexmodule', 'redirectwallet', ['type' => 'card'])|escape:'quotes':'UTF-8'}" title="{l s='Bank cards' mod='yandexmodule'}" class="yandex_money_bank_card yandex_money_payment">
                {l s='Bank cards' mod='yandexmodule'}
            </a>
        </p>
    </div>
</div>
