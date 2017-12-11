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

<div class="ya_result bg-warning">
    {l s='Ошибка платежа! Свяжитесь с поддержкой  укажите эти данные:' mod='yandexmodule'}
	<ol>
		{$foreach $post as $k => $p}
			<li>{$k|escape:'htmlall':'UTF-8} --- {$p|escape:'htmlall':'UTF-8}</li>
		{/foreach}
	</ol>
</div>
