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
{**
{include file="$tpl_dir./order-steps.tpl"}
**}

{if $nbProducts <= 0}
    <p class="alert alert-warning">
        {l s='Your basket is empty.' mod='yandexmodule'}
    </p>
{else}
    <form action="{$payment_link|escape:'quotes':'UTF-8'}" method="post" id="ym-billing-form">
        {if $empty || $error}
            <input type="hidden" name="cnf" value="1" checked />
        {/if}
        <div class="box cheque-box">
            <h3 class="page-subheading">
                {l s='Yandex.Billing (bank card, e-wallets).' mod='yandexmodule'}
            </h3>
            <p class="cheque-indent">
                <strong class="dark">
                    {l s='Order short information:' mod='yandexmodule'}
                </strong>
            </p>
            <p>
                - {l s='Order amount' mod='yandexmodule'}
                <span id="amount" class="price">{$total|escape:'htmlall':'UTF-8'}</span>
                {if $use_taxes == 1}
                    {l s='including tax' mod='yandexmodule'}
                {/if}
            </p>
            <br />
            {if $empty || $error}
                <div class="required form-group{if $error} form-error{/if}">
                    <label for="ym-billing-fio">{l s='Payer\'s full name ' mod='yandexmodule'}</label>
                    <input id="ym-billing-fio" class="is_required validate form-control" type="text" name="ym_billing_fio" value="{$fio|escape:'htmlall':'UTF-8'}" />
                </div>
            {else}
                <input type="hidden" name="formId" value="{$formId|escape:'htmlall':'UTF-8'}" />
                <input type="hidden" name="narrative" value="{$narrative|escape:'htmlall':'UTF-8'}" />
                <input type="hidden" name="fio" value="{$fio|escape:'htmlall':'UTF-8'}" />
                <input type="hidden" name="sum" value="{$total_sum|escape:'htmlall':'UTF-8'}" />
                <input type="hidden" name="quickPayVersion" value="2" />
            {/if}
        </div>
    </form>
    <p class="cart_navigation clearfix" id="cart_navigation">
        <a
                class="button-exclusive btn btn-default"
                href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}">
            <i class="icon-chevron-left"></i>{l s='Another payment methods' mod='yandexmodule'}
        </a>
        <button
                class="button btn btn-default button-medium"
                id="ym-billing-confirm-payment">
            <span>{l s='Confirm order' mod='yandexmodule'}<i class="icon-chevron-right right"></i></span>
        </button>
    </p>
    {if !$error && !$empty}
        <script type="text/javascript"> document.getElementById('ym-billing-form').submit(); </script>
    {/if}
{/if}