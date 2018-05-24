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

<p>{l s='Не удалось провести платёж' mod='yandexmodule'}</p>
{if $message}
    <p>{$message|escape:'htmlall':'UTF-8'}</p>
{/if}
