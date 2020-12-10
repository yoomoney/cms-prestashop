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

<div class="yoomoney_result bg-warning">
    {l s='Ошибка платежа! Свяжитесь с поддержкой  укажите эти данные:' mod='yoomoneymodule'}
	<ol>
		{$foreach $post as $k => $p}
			<li>{$k|escape:'htmlall':'UTF-8} --- {$p|escape:'htmlall':'UTF-8}</li>
		{/foreach}
	</ol>
</div>
