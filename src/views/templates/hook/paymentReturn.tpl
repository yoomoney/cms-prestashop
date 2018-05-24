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

<div class="box">
	<p class = "success">{l s='Payment captured' mod='yandexmodule'}</p>
	<h2>{l s='List of products in the order:' mod='yandexmodule'}</h2>
	<ul>
	{foreach from=$products item=product}
		<li>{if $product.download_hash}
			<a href="{$base_dir|escape:'htmlall':'UTF-8'}get-file.php?key={$product.filename|escape:'htmlall':'UTF-8'}-{$product.download_hash|escape:'htmlall':'UTF-8'}">
				<img src="{$img_dir|escape:'htmlall':'UTF-8'}icon/download_product.gif" class="icon" alt="" />
			</a>
			<a href="{$base_dir|escape:'htmlall':'UTF-8'}get-file.php?key={$product.filename|escape:'htmlall':'UTF-8'}-{$product.download_hash|escape:'htmlall':'UTF-8'}">
				{l s='Download' mod='yandexmodule'} {$product.product_name|escape:'htmlall':'UTF-8'}
			</a>
			{else}
			{$product.product_name|escape:'htmlall':'UTF-8'}
		{/if}
		</li>
	{/foreach}
	</ul>
</div>