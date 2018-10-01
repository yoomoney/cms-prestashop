<?php

namespace YandexMoneyModule\Models\Market;


class YandexMarketSettingsOfferType extends YandexMarketSettingsAbstract
{
    private $langArbitrary = 'Произвольный';
    private $langSimple    = 'Упрощенный с шаблоном названия предложения';
    private $langTemplate  = 'Шаблон названия';

    /**
     * @return string
     */
    public function html()
    {
        if ($this->getConfig('SIMPLE')) {
            $checkedSimple    = ' checked="checked"';
            $checkedArbitrary = '';
        } else {
            $checkedSimple    = '';
            $checkedArbitrary = ' checked="checked"';
        }

        $nameTemplate = $this->getConfig('NAME_TEMPLATE');

        $html = <<<HTML
        <div>
			<label>
				<input {$checkedArbitrary} type="radio" name="YA_MARKET_OFFER_TYPE_SIMPLE" value="">
                {$this->l($this->langArbitrary)}
			</label><br/>
			<label>
				<input {$checkedSimple} type="radio" name="YA_MARKET_OFFER_TYPE_SIMPLE" value="1">
                {$this->l($this->langSimple)}
			</label><br/>
			<div class="yandex-money-market-offer-name-template">
			    {$this->l($this->langTemplate)}
				<div>
					<input type="text" name="YA_MARKET_OFFER_TYPE_NAME_TEMPLATE" value="{$nameTemplate}"/>
				</div>
			</div>
		</div>
HTML;

        return $html;
    }

    /**
     * @return string
     */
    protected function getSettingsKeyPrefix()
    {
        return 'YA_MARKET_OFFER_TYPE_';
    }
}