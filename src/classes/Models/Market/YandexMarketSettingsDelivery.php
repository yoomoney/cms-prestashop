<?php

namespace YandexMoneyModule\Models\Market;


class YandexMarketSettingsDelivery extends YandexMarketSettingsAbstract
{
    private $defaultCurrency;

    private $langCost                = 'Стоимость';
    private $langDays                = 'Срок доставки';
    private $langDaysFrom            = 'от';
    private $langDaysTo              = 'до';
    private $langDaysOrderBefore     = 'При заказе до';
    private $langDaysMeasurementUnit = 'дн.';
    private $langUseDefault          = 'Использовать значение по умолчанию';
    private $langText                = 'доставка';
    private $langOrderBefore         = 'при заказе до';
    private $langDefaultValue        = '13:00 (по умолчанию для Маркета)';
    private $langOk                  = 'OK';
    private $langCancel              = 'Отмена';

    /**
     * @param string $defaultCurrency
     * @return string
     */
    public function htmlDeliveryList($defaultCurrency)
    {
        $this->defaultCurrency = $defaultCurrency;

        $html = '';

        for ($index = 1; $index <= 5; $index++) {
            $html .= $this->htmlDelivery($index);
        }

        return $html;
    }

    private function htmlDelivery($index)
    {
        $enabled     = $this->getConfig('ENABLED', $index);
        $cost        = $this->getConfig('COST', $index);
        $daysFrom    = $this->getConfig('DAYS_FROM', $index);
        $daysTo      = $this->getConfig('DAYS_TO', $index);
        $orderBefore = $this->getConfig('ORDER_BEFORE', $index);

        $htmlView = $this->htmlView($cost, $daysFrom, $daysTo, $orderBefore);
        $htmlEdit = $this->htmlEdit($index, $cost, $daysFrom, $daysTo, $orderBefore);

        $checked = $enabled ? 'checked="checked"' : '';

        $html = <<<HTML
            <div class="yandex-money-market-delivery yandex-money-market-js-editable">
                <input type="checkbox" name="YA_MARKET_DELIVERY_ENABLED[{$index}]" value="1" {$checked} />
                {$htmlView}
                {$htmlEdit}
                <i class="yandex-money-market-edit-on-button icon-edit"></i>
            </div>
HTML;
        return $html;
    }

    private function htmlView($cost, $daysFrom, $daysTo, $orderBefore)
    {
        $costValue        = (int)$cost;
        $daysValue        = empty($daysTo) || $daysFrom === $daysTo ? (int)$daysFrom : $daysFrom.'-'.$daysTo;
        $orderBeforeValue = $orderBefore ? $orderBefore.':00' : $this->l($this->langDefaultValue);

        $html = <<< HTML
            <span class="yandex-money-market-js-editable-view">
                <span class="yandex-money-market-delivery-cost">{$costValue}</span>
                {$this->defaultCurrency} 
                {$this->l($this->langText)} 
                <span class="delivery_days">{$daysValue}</span>
                {$this->l($this->langDaysMeasurementUnit)}
                {$this->l($this->langOrderBefore)} 
                <span class="yandex-money-market-delivery-order-before">{$orderBeforeValue}</span>
            </span>
HTML;
        return $html;
    }

    /**
     * @param int $index
     * @param string $cost
     * @param string $daysFrom
     * @param string $daysTo
     * @param int $orderBefore
     * @return string
     */
    private function htmlEdit($index, $cost, $daysFrom, $daysTo, $orderBefore)
    {
        $orderBeforeSelect = $this->htmlOrderBeforeSelect($index, $orderBefore);

        $html = <<<HTML
        <div class="yandex-money-market-delivery-edit yandex-money-market-js-editable-edit">
            <div class="form-group">
                {$this->l($this->langCost)} ({$this->defaultCurrency})
                <div>
                    <input type="text" class="yandex-money-market-delivery-cost" 
                    name="YA_MARKET_DELIVERY_COST[{$index}]" 
                    value="{$cost}" data-value="{$cost}"/>
                </div>
            </div>        
            <div class="form-group">
                {$this->l($this->langDays)}
                <div>
                    {$this->l($this->langDaysFrom)}
                    <input type="text" class="yandex-money-market-delivery-days-from" name="YA_MARKET_DELIVERY_DAYS_FROM[{$index}]"
                        value="{$daysFrom}" data-value="{$daysFrom}" min="0" max="31" size="3"/>
                    {$this->l($this->langDaysTo)}
                    <input type="text" class="yandex-money-market-delivery-days-to" name="YA_MARKET_DELIVERY_DAYS_TO[{$index}]"
                        value="{$daysTo}" data-value="{$daysTo}" min="0" max="31" size="3"/>
                    {$this->l($this->langDaysMeasurementUnit)}   
                </div>
            </div>        
            <div class="form-group">
                {$this->l($this->langDaysOrderBefore)}
                <div>
                    {$orderBeforeSelect}
                </div>
            </div>  
            <div class="form-group">
                <input type="button" class="btn btn-default edit_finish" value="{$this->l($this->langOk)}"/>
                <input type="button" class="btn btn-default edit_finish_reset" value="{$this->l($this->langCancel)}"/>
            </div>  
        </div> 
HTML;
        return $html;
    }

    /**
     * @param int $index
     * @param int $selectedTime
     * @return string
     */
    private function htmlOrderBeforeSelect($index, $selectedTime)
    {
        $useDefaultValue = $this->l($this->langUseDefault);

        $html = <<<HTML
            <select class="yandex-money-market-delivery-order-before"
                name="YA_MARKET_DELIVERY_ORDER_BEFORE[{$index}]"
                data-value="{$selectedTime}">
HTML;

        for ($time = 0; $time <= 24; $time++) {
            $html .= $this->htmlOrderBeforeOption($time, $selectedTime, $useDefaultValue);
        }
        $html .= '</select>';

        return $html;
    }

    /**
     * @param int $time
     * @param int $selectedTime
     * @param string $useDefaultValue
     * @return string
     */
    private function htmlOrderBeforeOption($time, $selectedTime, $useDefaultValue)
    {
        $selected = $time === (int)$selectedTime ? 'selected="selected"' : '';
        $timeText = $time === 0 ? $useDefaultValue : $time.':00';

        return <<<HTML
            <option value="{$time}" {$selected}>{$timeText}</option>
HTML;
    }

    /**
     * @return string
     */
    protected function getSettingsKeyPrefix()
    {
        return 'YA_MARKET_DELIVERY_';
    }
}