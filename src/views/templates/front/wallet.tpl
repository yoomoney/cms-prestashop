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
    {l s='Payment via YooMoney.' mod='yoomoneymodule'}
{/capture}

<h1 class="page-heading">
    {l s='Order details' mod='yoomoneymodule'}
</h1>

{assign var='current_step' value='payment'}
{*{include file="$tpl_dir./checkout-step.tpl"}*}

{if $nbProducts <= 0}
    <p class="alert alert-warning">
        {l s='Your cart is empty.' mod='yoomoneymodule'}
    </p>
{else}
    <form action="{$payment_link|escape:'quotes':'UTF-8'}" method="post">
        <input type="hidden" name="cnf" value="1" checked />
        <div class="box cheque-box">
            <h3 class="page-subheading">
                {l s='Payment via YooMoney.' mod='yoomoneymodule'}
            </h3>
            <p class="cheque-indent">
                <strong class="dark">
                    {l s='You selected payment via YooMoney.' mod='yoomoneymodule'} {l s='Short description of the order:' mod='yoomoneymodule'}
                </strong>
            </p>
            <p>
                - {l s='Total amount' mod='yoomoneymodule'}
                {*<span id="amount" class="price">{displayPrice price=$total}</span>*}
                {if $use_taxes == 1}
                    {l s='(incl. VAT)' mod='yoomoneymodule'}
                {/if}
            </p>
        </div>
        <p class="cart_navigation clearfix" id="cart_navigation">
        	<a class="button-exclusive btn btn-default" href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}">
                <i class="icon-chevron-left"></i>{l s='Other payment methods' mod='yoomoneymodule'}
            </a>
            <button class="button btn btn-default button-medium" type="submit">
                <span>{l s='I confirm the order' mod='yoomoneymodule'}<i class="icon-chevron-right right"></i></span>
            </button>
        </p>
    </form>
{/if}
