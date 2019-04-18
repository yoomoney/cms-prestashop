<?php

namespace YandexMoneyModule\Models\Market;


class YandexMarketSettingsVat extends YandexMarketSettingsAbstract
{
    private $langExportVat = 'Добавить налоговые ставки в предложения';
    private $lang20        = '20%';
    private $lang10        = '10%';
    private $lang20120     = '20/120';
    private $lang10110     = '10/110';
    private $lang0         = '0%';
    private $langNoVat     = 'НДС не облагается';

    /**
     * @param array $psTaxes
     * @return string
     */
    public function htmlVatList($psTaxes)
    {
        $checked = $this->getConfig('ENABLED') ? ' checked="checked"' : '';

        $html = <<<HTML
        <div class="form-group">
            <label>
                <input type="checkbox" name="YA_MARKET_VAT_ENABLED" value="1" {$checked}>
                {$this->l($this->langExportVat)}
            </label>
        </div>
        <div class="form-group yandex-money-market-val-list">
HTML;

        foreach ($psTaxes as $tax) {
            $html .= $this->htmlVatItem($tax['id_tax'], $tax['name']);
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * @param string $taxId
     * @param string $name
     * @return string
     */
    private function htmlVatItem($taxId, $name)
    {
        $selectedVat = $this->getConfig('LIST', $taxId);
        $select      = $this->htmlSelect($taxId, $selectedVat);

        $html = <<<HTML
            <div class="form-group">
                {$name}
                <div>
                    {$select}
                </div>
            </div>
HTML;

        return $html;
    }

    /**
     * @param string $id
     * @param string $selectedVat
     * @return string
     */
    private function htmlSelect($id, $selectedVat)
    {
        $html = <<<HTML
                <select name="YA_MARKET_VAT_LIST[{$id}]" data-value="{$selectedVat}">
HTML;
        foreach ($this->getTaxList() as $vatKey => $name) {
            $selected = $vatKey === $selectedVat ? 'selected="selected"' : '';
            $html     .= '<option value="'.$vatKey.'" '.$selected.'>'.$name.'</option>';
        }
        $html .= '</select>';

        return $html;
    }

    /**
     * @return array
     */
    private function getTaxList()
    {
        return array(
            'VAT_20'     => $this->l($this->lang20),
            'VAT_10'     => $this->l($this->lang10),
            'VAT_20_120' => $this->l($this->lang20120),
            'VAT_10_110' => $this->l($this->lang10110),
            'VAT_0'      => $this->l($this->lang0),
            'NO_VAT'     => $this->l($this->langNoVat),
        );
    }

    /**
     * @return string
     */
    protected function getSettingsKeyPrefix()
    {
        return 'YA_MARKET_VAT_';
    }
}