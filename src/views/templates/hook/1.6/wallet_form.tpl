{**
* Module is prohibited to sales! Violation of this condition leads to the deprivation of the license!
*
* @author    YooMoney <cms@yoomoney.ru>
* @copyright © 2020 "YooMoney", NBСO LLC
* @license   https://yoomoney.ru/doc.xml?id=527052
*
* @category  Front Office Features
* @package   YooMoney Payment Solution
*}

<div class="row">
    <div class="col-xs-12 col-md-6">
        <p class="payment_module">
            <a href="{$link->getModuleLink('yoomoneymodule', 'redirectwallet', ['type' => 'wallet'])|escape:'quotes':'UTF-8'}" title="{l s='YooMoney' mod='yoomoneymodule'}" class="yoomoney_yoo_money yoomoney_payment">
                {l s='YooMoney' mod='yoomoneymodule'}
            </a>
        </p>
    </div>
</div>
<div class="row">
    <div class="col-xs-12 col-md-6">
        <p class="payment_module">
            <a href="{$link->getModuleLink('yoomoneymodule', 'redirectwallet', ['type' => 'card'])|escape:'quotes':'UTF-8'}" title="{l s='Bank cards' mod='yoomoneymodule'}" class="yoomoney_bank_card yoomoney_payment">
                {l s='Bank cards' mod='yoomoneymodule'}
            </a>
        </p>
    </div>
</div>
