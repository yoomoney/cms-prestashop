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

{capture name=path}{l s='Waiting for capture' mod='yandexmodule'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}
<div class="box">
	<h2>{l s='Waiting for capture' mod='yandexmodule'}</h2>
	<p>{l s='Your payment is not captured yet, check the order status in your Merchant Profile.' mod='yandexmodule'}</p>
	<p>{l s='If you did not recieve the payment notification, provide us with the cart numbe:' mod='yandexmodule'} <strong>{$ordernumber|intval},</strong> <b><a href="{$link->getPageLink('contact-form', true)|escape:'quotes':'UTF-8'}">{l s='Техническая поддержка.' mod='yandexmodule'}</a></b></p>
</div>