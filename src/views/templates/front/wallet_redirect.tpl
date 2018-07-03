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

{capture name=path}
    {l s='Payment with Yandex.Billing.' mod='yandexmodule'}
{/capture}

<h1 class="page-heading">
    {l s='Order description' mod='yandexmodule'}
</h1>

{assign var='current_step' value='payment'}


<form id="wallet-form" method="POST" action="https://money.yandex.ru/quickpay/confirm.xml">
    <input type="hidden" name="receiver" value="{$receiver}">
    <input type="hidden" name="label" value="{$orderId}">
    <input type="hidden" name="targets" value="{$targets}">
    <input type="hidden" name="quickpay-form" value="shop">
    <input type="hidden" name="sum" value="{$amount}" data-type="number">
    <input type="hidden" name="need-fio" value="false">
    <input type="hidden" name="need-email" value="false">
    <input type="hidden" name="need-phone" value="false">
    <input type="hidden" name="need-address" value="false">
    <input type="hidden" name="successURL" value="{$successURL}">
    <input type="hidden" name="paymentType" value="{$paymentType}">
    <div class="row">
        <div class="col-xs-12 col-md-6">
            <p class="payment_module">
                <button type="submit" class="yandex_money_yandex_money yandex_money_payment">
                    {l s='Yandex.Money' mod='yandexmodule'}
                </button>
            </p>
        </div>
    </div>
</form>
<script type="text/javascript"> document.getElementById('wallet-form').submit(); </script>