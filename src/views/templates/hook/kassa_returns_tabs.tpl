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

<div class="center-block col-lg-7">
    <script>
        jQuery(document).ready(function() {
            jQuery('#return-tab-panel a').click(function (e) {
                e.preventDefault()
                jQuery(this).tab('show')
            });
        });
    </script>
    <ul class="nav nav-tabs" id="return-tab-panel">
        <li class="active">
            <a href="#kassa_return">
                <i class="icon-time"></i>
                {l s='Return' mod='yandexmodule'}</span>
            </a>
        </li>
        <li>
            <a href="#kassa_return_table">
                <i class="icon-time"></i>
                {l s='History' mod='yandexmodule'} {*<span class="badge">{$kassa_returns|@count}</span>*}
            </a>
        </li>
    </ul>