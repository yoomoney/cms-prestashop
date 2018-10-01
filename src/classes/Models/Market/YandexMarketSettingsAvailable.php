<?php

namespace YandexMoneyModule\Models\Market;


class YandexMarketSettingsAvailable extends YandexMarketSettingsAbstract
{
    private $langNonZeroCountGoods   = 'Товары в наличии';
    private $langIfZeroCountGoods    = 'Товары отсутствующие на складе';
    private $langDeliveryDescription = 'доставка до места';
    private $langPickupDescription   = 'самовывоз из пункта заказа';
    private $langStoreDescription    = 'покупка без предварительного заказа';
    private $langDontUnload          = 'Не выгружать';
    private $langReady               = 'Готов к отправке';
    private $langToOrder             = 'На заказ';
    private $langViewDontUpload      = 'не будут выгружены';
    private $langViewWillUpload      = 'будут выгружены со статусом';
    private $langViewReady           = 'готов к отправке';
    private $langViewToOrder         = 'на заказ';
    private $langViewWithAvailable   = 'и доступны';
    private $langViewDelivery        = 'доставкой';
    private $langViewPickup          = 'самовывозом';
    private $langViewStore           = 'покупкой на месте';
    private $langOk                  = 'OK';
    private $langCancel              = 'Отмена';

    /**
     * @return string
     */
    public function htmlAvailableList()
    {
        $html = $this->htmlAvailable('non-zero-quantity', $this->l($this->langNonZeroCountGoods))
            .$this->htmlAvailable('zero-quantity', $this->l($this->langIfZeroCountGoods));

        return $html;
    }

    /**
     * @param $index
     * @param $statusName
     * @return string
     */
    private function htmlAvailable($index, $statusName)
    {
        $enabled   = $this->getConfig('ENABLED', $index);
        $available = $this->getConfig('AVAILABLE', $index, 'none');
        $delivery  = $this->getConfig('DELIVERY', $index);
        $pickup    = $this->getConfig('PICKUP', $index);
        $store     = $this->getConfig('STORE', $index);

        $enabledCheckbox = $this->htmlCheckbox($index, 'ENABLED', $enabled, '');

        $htmlView = $this->htmlView($statusName);
        $htmlEdit = $this->htmlEdit($index, $statusName, $available, $delivery, $pickup, $store);

        $html = <<<HTML
            <div class="yandex-money-market-available yandex-money-market-js-editable">
                {$enabledCheckbox}
                {$htmlView}
                {$htmlEdit}
                <i class="yandex-money-market-edit-on-button icon-edit"></i>
            </div>
HTML;

        return $html;
    }

    /**
     * @param string $statusName
     * @return string
     */
    private function htmlView($statusName)
    {
        $html = <<< HTML
            <span class="yandex-money-market-js-editable-view">
                <span class="yandex-money-market-available-status">{$statusName}</span>
                <span class="available_dont_upload">{$this->l($this->langViewDontUpload)}</span>
                <span class="available_will_upload">
                    {$this->l($this->langViewWillUpload)}
                    <span class="yandex-money-market-available-with-ready">{$this->l($this->langViewReady)}</span>
                    <span class="yandex-money-market-available-with-to-order">{$this->l($this->langViewToOrder)}</span>
                    <span class="yandex-money-market-available-view-available-list">
                        {$this->l($this->langViewWithAvailable)}
                        <span class="yandex-money-market-available-options-list yandex-money-market-available-delivery">{$this->langViewDelivery}</span>
                        <span class="yandex-money-market-available-options-list yandex-money-market-available-pickup">{$this->langViewPickup}</span>
                        <span class="yandex-money-market-available-options-list yandex-money-market-available-store last">{$this->langViewStore}</span>
                    </span>
                </span>
            </span>
HTML;

        return $html;
    }

    /**
     * @param string $index
     * @param string $statusName
     * @param $available
     * @param $delivery
     * @param $pickup
     * @param $store
     * @return string
     */
    private function htmlEdit($index, $statusName, $available, $delivery, $pickup, $store)
    {
        $availableSelect  = $this->htmlSelect($index, $available);
        $deliveryCheckbox = $this->htmlCheckbox($index, 'DELIVERY', $delivery,
            $this->l($this->langDeliveryDescription));
        $pickupCheckbox   = $this->htmlCheckbox($index, 'PICKUP', $pickup, $this->l($this->langPickupDescription));
        $storeCheckbox    = $this->htmlCheckbox($index, 'STORE', $store, $this->l($this->langStoreDescription));

        $html = <<<HTML
        <div class="yandex-money-market-available-edit yandex-money-market-js-editable-edit">
            {$statusName}
            <div>
                {$availableSelect}
            </div>        
            <div>
                {$deliveryCheckbox}
            </div>
            <div>
                {$pickupCheckbox}
            </div>
            <div>
                {$storeCheckbox}
            </div>  
            <div>
                <input type="button" class="btn btn-default edit_finish" value="{$this->l($this->langOk)}"/>
                <input type="button" class="btn btn-default edit_finish_reset" value="{$this->l($this->langCancel)}"/>
            </div>
        </div>
HTML;
        return $html;
    }

    /**
     * @param string $index
     * @param string $available
     * @return string
     */
    private function htmlSelect($index, $available)
    {
        $options = array(
            'none'  => $this->l($this->langDontUnload),
            'true'  => $this->l($this->langReady),
            'false' => $this->l($this->langToOrder),
        );

        $html = '<select name="YA_MARKET_AVAILABLE_AVAILABLE['.$index.']" data-value="'.$available.'">';

        foreach ($options as $value => $text) {
            $html .= $this->htmlOption($value, $text, $available);
        }
        $html .= '</select>';

        return $html;
    }

    /**
     * @param string $value
     * @param string $text
     * @param string $available
     * @return string
     */
    private function htmlOption($value, $text, $available)
    {
        $selected = $value === $available ? 'selected="selected"' : '';

        return <<<HTML
            <option value="{$value}" {$selected}>{$text}</option>
HTML;
    }

    /**
     * @param $index
     * @param $field
     * @param string $value
     * @param string $text
     * @return string
     */
    private function htmlCheckbox($index, $field, $value, $text)
    {
        $checked = $value ? 'checked="checked"' : '';
        $classField = strtolower($field);

        return <<<HTML
            <label>
                <input type="checkbox" value="1" data-value="{$value}" {$checked}
                    class="yandex-money-market-available-{$classField}" 
                    name="YA_MARKET_AVAILABLE_{$field}[{$index}]" /> 
                {$text}
            </label>              
HTML;
    }

    /**
     * @return string
     */
    protected function getSettingsKeyPrefix()
    {
        return 'YA_MARKET_AVAILABLE_';
    }
}