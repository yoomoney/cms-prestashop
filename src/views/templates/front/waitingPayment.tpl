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

{capture name=path}{l s='Ожидание платежа' mod='yandexmodule'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}
<div class="box">
	<h2>{l s='Ожидание платежа' mod='yandexmodule'}</h2>
	<p>{l s='Ваш платёж ещё не подтверждён, статус заказа вы можете узнать в личном кабинете.' mod='yandexmodule'}</p>
	<p>{l s='Если вы не получили уведомление об оплате, напишите нам номер корзины:' mod='yandexmodule'} <strong>{$ordernumber|intval},</strong> <b><a href="{$link->getPageLink('contact-form', true)|escape:'quotes':'UTF-8'}">{l s='Техническая поддержка.' mod='yandexmodule'}</a></b></p>
</div>