<?php

namespace YandexMoneyModule\Models\Market;


class YandexMarketSettingsExportLink extends YandexMarketSettingsAbstract
{
    private $langCopy  = 'Скопировать ссылку';
    private $langCoped = 'Ссылка скопирована';

    /**
     * @return string
     */
    public function html()
    {
        $link = $this->getConfig('URL');

        $html = <<<HTML
        <div>
            <input disabled="disabled" type="text" value="{$link}" 
                class="yandex-money-market-export-link-url"
                data-message="{$this->l($this->langCoped)}">
            <i class="yandex-money-market-copy-url icon-copy" title="{$this->l($this->langCopy)}"></i>
		</div>
HTML;

        return $html;
    }

    /**
     * @return string
     */
    protected function getSettingsKeyPrefix()
    {
        return 'YA_MARKET_EXPORT_LINK_';
    }
}