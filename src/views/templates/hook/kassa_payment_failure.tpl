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

<p>{l s='Не удалось провести платёж' mod='yoomoneymodule'}</p>
{if $message}
    <p>{$message|escape:'htmlall':'UTF-8'}</p>
{/if}
