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

{capture name=path}{l s='Waiting for capture' mod='yoomoneymodule'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}
<div class="box">
	<h2>{l s='Waiting for capture' mod='yoomoneymodule'}</h2>
	<p>{l s='Your payment is not captured yet, check the order status in your Merchant Profile.' mod='yoomoneymodule'}</p>
	<p>{l s='If you did not recieve the payment notification, provide us with the cart numbe:' mod='yoomoneymodule'} <strong>{$ordernumber|intval},</strong> <b><a href="{$link->getPageLink('contact-form', true)|escape:'quotes':'UTF-8'}">{l s='Техническая поддержка.' mod='yoomoneymodule'}</a></b></p>
</div>