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

<div class="box">
	<p class = "success">{l s='Payment captured' mod='yoomoneymodule'}</p>
	<h2>{l s='List of products in the order:' mod='yoomoneymodule'}</h2>
	<ul>
	{foreach from=$products item=product}
		<li>{if $product.download_hash}
			<a href="{$base_dir|escape:'htmlall':'UTF-8'}get-file.php?key={$product.filename|escape:'htmlall':'UTF-8'}-{$product.download_hash|escape:'htmlall':'UTF-8'}">
				<img src="{$img_dir|escape:'htmlall':'UTF-8'}icon/download_product.gif" class="icon" alt="" />
			</a>
			<a href="{$base_dir|escape:'htmlall':'UTF-8'}get-file.php?key={$product.filename|escape:'htmlall':'UTF-8'}-{$product.download_hash|escape:'htmlall':'UTF-8'}">
				{l s='Download' mod='yoomoneymodule'} {$product.product_name|escape:'htmlall':'UTF-8'}
			</a>
			{else}
			{$product.product_name|escape:'htmlall':'UTF-8'}
		{/if}
		</li>
	{/foreach}
	</ul>
</div>