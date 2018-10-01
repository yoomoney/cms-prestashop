<?php

namespace YandexMoneyModule\Models\Market;


class YandexMarketSettingsAdditionalCondition extends YandexMarketSettingsAbstract
{
    const MAX_CATEGORY_NUMBER = 3;

    private $categoryTree;

    /**
     * @var array() [parent_id][category_id] => category_name
     */
    private $categories = array();

    private $langName               = 'Название условия';
    private $langTag                = 'Тег';
    private $langStaticValue        = 'Постоянное значение';
    private $langDataValue          = 'Значение из карточки товара';
    private $langForCategories      = 'Для категорий';
    private $langMore               = 'Добавить условие';
    private $langMakeTag            = 'задает параметру';
    private $langWithValue          = 'значение';
    private $langForCategory        = 'для';
    private $langForAllCategories   = 'всех категорий';
    private $langForMoreCategories  = 'и еще %s кат.';
    private $langJoin               = 'Одинаковые теги в предложении';
    private $langJoinView           = 'объединять в один тег';
    private $langDontJoinView       = 'оставить в нескольких тегах';
    private $langAllCategories      = 'для всех';
    private $langSelectedCategories = 'для выбранных';
    private $langOk                 = 'OK';
    private $langCancel             = 'Отменить';
    private $langDelete             = 'Удалить';

    private $productFields = array(
        'id_product',
        'id_supplier',
        'id_manufacturer',
        'id_category_default',
        'id_shop_default',
        'id_tax_rules_group',
        'on_sale',
        'online_only',
        'ean13',
        'upc',
        'ecotax',
        'quantity',
        'minimal_quantity',
        'price',
        'wholesale_price',
        'unity',
        'unit_price_ratio',
        'additional_shipping_cost',
        'reference',
        'supplier_reference',
        'location',
        'width',
        'height',
        'depth',
        'weight',
        'out_of_stock',
        'quantity_discount',
        'customizable',
        'uploadable_files',
        'text_fields',
        'active',
        'redirect_type',
        'id_product_redirected',
        'available_for_order',
        'available_date',
        'condition',
        'show_price',
        'indexed',
        'visibility',
        'cache_is_pack',
        'cache_has_attachments',
        'is_virtual',
        'cache_default_attribute',
        'date_add',
        'date_upd',
        'advanced_stock_management',
        'pack_stock_type',
        'id_shop',
        'id_product_attribute',
        'product_attribute_minimal_quantity',
        'description',
        'description_short',
        'available_now',
        'available_later',
        'link_rewrite',
        'meta_description',
        'meta_keywords',
        'meta_title',
        'name',
        'id_image',
        'legend',
        'manufacturer_name',
        'category_default',
        'new',
        'orderprice',
        'allow_oosp',
        'category',
        'link',
        'attribute_price',
        'price_tax_exc',
        'price_without_reduction',
        'reduction',
        'specific_prices',
        'quantity_all_versions',
        'features',
        'attachments',
        'virtual',
        'pack',
        'packItems',
        'nopackprice',
        'customization_required',
        'rate',
        'tax_name'
    );

    /**
     * @param $categories
     * @return string
     */
    public function htmlAdditionalConditionList($categories)
    {
        $this->categories = $categories;

        $typeValues = $this->getConfig('TYPE_VALUE');
        $maxIndex   = empty($typeValues) ? 0 : max(array_keys($typeValues));

        $html = '<div class="yandex-money-market-additional-condition-container">
        <div class="yandex-money-market-additional-condition-list">';
        for ($index = 1; $index <= $maxIndex; $index++) {
            $html .= $this->htmlAdditionalConditionItem($index);
        }
        $html .= $this->htmlAdditionalConditionItem('');

        $html .= <<<HTML
            </div>
            <div class="form-group">
                <a onclick="return false;" data-index="{$index}" class="yandex-money-market-additional-condition-more">
                   {$this->l($this->langMore)}
               </a>
            </div>
        </div>
HTML;

        return $html;
    }

    /**
     * @param $index
     * @return string
     */
    private function htmlAdditionalConditionItem($index)
    {
        if ($index === '') {
            $enabled     = '';
            $name        = '';
            $tag         = '';
            $typeValue   = 'static';
            $staticValue = '';
            $dataValue   = '';
            $addTemplate = 'yandex-money-market-additional-condition-template';
            $fieldName   = 'data-name';
            $forAllCat   = '1';
            $join        = '';
            $checkedList = array();
        } else {
            $enabled     = $this->getConfig('ENABLED', $index);
            $name        = $this->getConfig('NAME', $index);
            $tag         = $this->getConfig('TAG', $index);
            $typeValue   = $this->getConfig('TYPE_VALUE', $index);
            $staticValue = $this->getConfig('STATIC_VALUE', $index);
            $dataValue   = $this->getConfig('DATA_VALUE', $index);
            $forAllCat   = $this->getConfig('FOR_ALL_CAT', $index);
            $join        = $this->getConfig('JOIN', $index);
            $checkedList = (array)$this->getConfig('CATEGORIES', $index);
            $addTemplate = '';
            $fieldName   = 'name';
        }

        if (empty($typeValue)) {
            return '';
        }

        $htmlView = $this->htmlView($name, $tag, $typeValue, $staticValue, $dataValue, $forAllCat, $checkedList);
        $htmlEdit = $this->htmlEdit($index, $name, $tag, $typeValue, $staticValue, $dataValue, $forAllCat, $checkedList,
            $join, $fieldName);

        $checked = $enabled ? 'checked="checked"' : '';

        $html = <<<HTML
            <div class="yandex-money-market-js-editable yandex-money-market-additional-condition {$addTemplate}">
                <input type="checkbox" {$fieldName}="YA_MARKET_ADDITIONAL_CONDITION_ENABLED[{$index}]" value="1" {$checked} />
                {$htmlView}
                {$htmlEdit}
                <i class="yandex-money-market-edit-on-button icon-edit"></i>
            </div>
HTML;
        return $html;
    }

    /**
     * @param string $name
     * @param string $tag
     * @param string $typeValue
     * @param string $staticValue
     * @param string $dataValue
     * @param string $forAllCat
     * @param array $checkedList
     * @return string
     */
    private function htmlView(
        $name,
        $tag,
        $typeValue,
        $staticValue,
        $dataValue,
        $forAllCat,
        array $checkedList
    ) {
        $value = $typeValue === 'static'
            ? $staticValue
            : $dataValue;

        if ($forAllCat) {
            $categoryList = $this->l($this->langForAllCategories);
        } else {
            $categories = array();
            foreach ($this->categories as $categoryGroup) {
                foreach ($categoryGroup as $category) {
                    if (in_array($category['infos']['id_category'], $checkedList)) {
                        $categories[] = $category['infos']['name'];
                    }
                }
            }
            $count = count($categories);
            if ($count <= self::MAX_CATEGORY_NUMBER) {
                $categoryList = implode(', ', $categories);
            } else {
                $categoryList = implode(', ', array_slice($categories, 0, self::MAX_CATEGORY_NUMBER));
                $categoryList .= ' '.sprintf($this->l($this->langForMoreCategories),
                        $count - self::MAX_CATEGORY_NUMBER);
            }
        }

        $html = <<< HTML
            <span class="yandex-money-market-js-editable-view">
                <span class="yandex-money-market-additional-condition-name">{$name}</span>
                {$this->l($this->langMakeTag)}
                &lt;<span class="yandex-money-market-additional-condition-tag">{$tag}</span>&gt;
                {$this->l($this->langWithValue)}
                <em><span class="yandex-money-market-additional-condition-value">{$value}</span></em>
                {$this->l($this->langForCategory)}
                <span class="yandex-money-market-additional-condition-category-list">{$categoryList}</span>
            </span>
HTML;
        return $html;
    }

    /**
     * @param $index
     * @param $name
     * @param $tag
     * @param $typeValue
     * @param $staticValue
     * @param $dataValue
     * @param $forAllCat
     * @param array $checkedList
     * @param string $join
     * @param $fieldName
     * @return string
     */
    private function htmlEdit(
        $index,
        $name,
        $tag,
        $typeValue,
        $staticValue,
        $dataValue,
        $forAllCat,
        $checkedList,
        $join,
        $fieldName
    ) {
        $dataValueSelect = $this->htmlProductDataSelect($index, $dataValue, $fieldName);

        if ($typeValue === 'static') {
            $staticValueChecked = ' checked="checked"';
            $dataValueChecked   = '';
        } else {
            $staticValueChecked = '';
            $dataValueChecked   = ' checked="checked"';
        }

        if ($forAllCat) {
            $allCategoriesChecked      = ' checked="checked"';
            $selectedCategoriesChecked = '';
            $classCategoryTree         = ' yandex-money-market-hidden-element';
        } else {
            $allCategoriesChecked      = '';
            $selectedCategoriesChecked = ' checked="checked"';
            $classCategoryTree         = '';
        }

        if ($join) {
            $joinChecked     = ' checked="checked"';
            $dontJoinChecked = '';
        } else {
            $joinChecked     = '';
            $dontJoinChecked = ' checked="checked"';
        }

        $html = <<<HTML
        <div class="yandex-money-market-js-editable-edit yandex-money-market-category-tree-container">
            <div class="form-group">
                {$this->l($this->langName)}
                <div>
                    <input type="text" {$fieldName}="YA_MARKET_ADDITIONAL_CONDITION_NAME[{$index}]" value="{$name}"  data-value="{$name}" />
                </div>
            </div>        
            <div class="form-group">
                {$this->l($this->langTag)}
                <div>
                    <input type="text" {$fieldName}="YA_MARKET_ADDITIONAL_CONDITION_TAG[{$index}]" value="{$tag}" data-value="{$tag}"/>
                </div>
            </div>        

            <div class="form-group">
                <label class="yandex-money-market-first-letter-uppercase">
                    <input type="radio" {$fieldName}="YA_MARKET_ADDITIONAL_CONDITION_TYPE_VALUE[{$index}]" value="static" data-value="$typeValue" {$staticValueChecked}/>
                    {$this->l($this->langStaticValue)}
                </label>
                <div>
                    <input type="text" {$fieldName}="YA_MARKET_ADDITIONAL_CONDITION_STATIC_VALUE[{$index}]" value="{$staticValue}" data-value="{$staticValue}"/>
                </div>
            </div>        
            <div class="form-group">
                <label class="yandex-money-market-first-letter-uppercase">
                    <input type="radio" {$fieldName}="YA_MARKET_ADDITIONAL_CONDITION_TYPE_VALUE[{$index}]" value="data" data-value="$typeValue" {$dataValueChecked}/>
                    {$this->l($this->langDataValue)}
                </label>
                <div>
                    {$dataValueSelect}
                </div>
            </div>
            
            <div class="form-group">
                {$this->l($this->langJoin)}
                <div>
                    <label>
                        <input type="radio" {$joinChecked} {$fieldName}="YA_MARKET_ADDITIONAL_CONDITION_JOIN[{$index}]" value="1" data-value="{$join}"/>
                            {$this->l($this->langJoinView)}
                    </label>
                    <br>
                    <label>
                        <input type="radio" {$dontJoinChecked} {$fieldName}="YA_MARKET_ADDITIONAL_CONDITION_JOIN[{$index}]" value="" data-value="{$join}"/>
                        {$this->l($this->langDontJoinView)}
                    </label>
                </div>
            </div>        
            
            <div class="form-group">
                {$this->l($this->langForCategories)}
                <div>
                    <label>
                        <input type="radio" {$allCategoriesChecked} 
                            {$fieldName}="YA_MARKET_ADDITIONAL_CONDITION_FOR_ALL_CAT[{$index}]" 
                            class="yandex-money-market-category-tree-switcher" value="1"/>
                            {$this->l($this->langAllCategories)}
                    </label>
                    <br/>
                    <label>
                        <input type="radio" {$selectedCategoriesChecked} 
                            {$fieldName}="YA_MARKET_ADDITIONAL_CONDITION_FOR_ALL_CAT[{$index}]" 
                            class="yandex-money-market-category-tree-switcher" value=""/> 
                        {$this->l($this->langSelectedCategories)}
                    </label>
                </div>
            </div>        

            <div class="form-group yandex-money-market-category-tree {$classCategoryTree}">
                {$this->getCategoryTree()->htmlAdditionalCategoryList($this->categories, $checkedList,
            $fieldName.'="YA_MARKET_ADDITIONAL_CONDITION_CATEGORIES['.$index.'][]"')}
            </div>
            
            <div class="form-group">
                <input type="button" class="btn btn-default edit_finish" value="{$this->l($this->langOk)}"/>
                <input type="button" class="btn btn-default edit_finish_reset" value="{$this->l($this->langCancel)}"/>
                <input type="button" class="btn btn-default edit_finish_delete" value="{$this->l($this->langDelete)}"/>
            </div>  
        </div>
HTML;
        return $html;
    }

    /**
     * @param $index
     * @param int $selectedField
     * @param $fieldName
     * @return string
     */
    private function htmlProductDataSelect($index, $selectedField, $fieldName)
    {
        $html = '<select '.$fieldName.'="YA_MARKET_ADDITIONAL_CONDITION_DATA_VALUE['.$index.']" data-value='.$selectedField.'>';
        foreach ($this->productFields as $productField) {
            $selected = $productField === $selectedField ? 'selected="selected"' : '';
            $text     = $productField;
            $html     .= <<<HTML
            <option value="{$productField}" {$selected}>{$text}</option>
HTML;
        }
        $html .= '</select>';

        return $html;
    }

    /**
     * @return YandexMarketSettingsCategoryTree
     */
    private function getCategoryTree()
    {
        if (!$this->categoryTree) {
            $this->categoryTree = new YandexMarketSettingsCategoryTree($this->settings);
        }

        return $this->categoryTree;
    }

    /**
     * @return string
     */
    protected function getSettingsKeyPrefix()
    {
        return 'YA_MARKET_ADDITIONAL_CONDITION_';
    }
}