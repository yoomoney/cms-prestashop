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
    {l s='Payment via bank card.' mod='yandexmodule'}
{/capture}

<h1 class="page-heading">
    {l s='Payment details' mod='yandexmodule'}
</h1>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $nbProducts <= 0}
	<p class="alert alert-warning">
        {l s='Your cart is empty.' mod='yandexmodule'}
    </p>
{else}
    <form action="{$payment_link|escape:'quotes':'UTF-8'}" method="post">
	<input type="hidden" name="cnf" value="1" checked />
        <div class="box cheque-box">
            <h3 class="page-subheading">
                {l s='Сredit card payment.' mod='yandexmodule'}
            </h3>
            <p class="cheque-indent">
                <strong class="dark">
                    {l s='You selected payment via bank card.' mod='yandexmodule'} {l s='Following errors occured during the order processing:' mod='yandexmodule'}
                </strong>
            </p>
            <p>
                <div class="alert alert-danger">
					<p>{l s='Errors count ' mod='yandexmodule'} {$errors|count}</p>
					<ol>
						{foreach $errors as $e}
							<li>{$e|escape:'htmlall':'UTF-8'}</li>
						{/foreach}
					</ol>
				</div>
            </p>
        </div>
        <p class="cart_navigation clearfix" id="cart_navigation">
        	<a 
            class="button-exclusive btn btn-default" 
            href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}">
                <i class="icon-chevron-left"></i>{l s='Other payment methods' mod='yandexmodule'}
            </a>
        </p>
    </form>
{/if}