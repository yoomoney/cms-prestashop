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

{capture name=path}
    {l s='Payment with Billing.' mod='yoomoneymodule'}
{/capture}

<h1 class="page-heading">
    {l s='Order description' mod='yoomoneymodule'}
</h1>

{assign var='current_step' value='payment'}

{extends file='page.tpl'}

{block name="content"}
<form id="wallet-form" method="POST" action="https://yoomoney.ru/quickpay/confirm.xml">
    <input type="hidden" name="receiver" value="{$receiver}">
    <input type="hidden" name="label" value="{$orderId}">
    <input type="hidden" name="quickpay-form" value="shop">
    <input type="hidden" name="targets" value="{$targets}">
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
                <button type="submit" class="yoomoney_yoo_money yoomoney_payment">
                    {l s='YooMoney' mod='yoomoneymodule'}
                </button>
            </p>
        </div>
    </div>
</form>
<script type="text/javascript"> document.getElementById('wallet-form').submit(); </script>
{/block}
