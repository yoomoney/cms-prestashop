<?php

namespace YandexMoneyModule\Models\Market;


class YandexMarketSettingsCategoryTree extends YandexMarketSettingsAbstract
{
    const ROOT_CATEGORY_ID = 2;

    /**
     * @var array() [parent_id][category_id] => category_name
     */
    private $categories = array();

    private $langAll         = 'Все';
    private $langSelected    = 'Выбранные';
    private $langCollapseAll = 'Свернуть всё';
    private $langExpandAll   = 'Развернуть всё';
    private $langCheckAll    = 'Отметить всё';
    private $langUncheckAll  = 'Убрать все отметки';

    /**
     * @param $psCategories
     */
    private function initCategories($psCategories)
    {
        $this->categories = $psCategories;
    }

    /**
     * @param $psCategories
     * @return string
     */
    public function htmlCategoryList($psCategories)
    {
        $this->initCategories($psCategories);

        if ($this->getConfig('ALL_ENABLED')) {
            $checkedAll      = ' checked="checked"';
            $checkedSelected = '';
            $hiddenClass     = 'yandex-money-market-hidden-element';
        } else {
            $checkedAll      = '';
            $checkedSelected = ' checked="checked"';
            $hiddenClass     = '';
        }

        $checkedList = (array)$this->getConfig('LIST');

        $market_category_tree = $this->getCategoryTree($checkedList, 'name="YA_MARKET_CATEGORY_LIST[]"');

        $html = <<<HTML
        <div class="yandex-money-market-category-tree-container">
			<label>
				<input {$checkedAll} type="radio" name="YA_MARKET_CATEGORY_ALL_ENABLED" class="yandex-money-market-category-tree-switcher" value="1">
                {$this->l($this->langAll)}
			</label><br/>
			<label>
				<input {$checkedSelected} type="radio" name="YA_MARKET_CATEGORY_ALL_ENABLED" class="yandex-money-market-category-tree-switcher" value="">
                {$this->l($this->langSelected)}
			</label><br/>
			<div class="panel panel-default yandex-money-market-category-tree {$hiddenClass}">
				{$market_category_tree}
			</div>
		</div>
HTML;

        return $html;
    }

    /**
     * @param $psCategories
     * @param array $checkedList
     * @param $inputName
     * @return string
     */
    public function htmlAdditionalCategoryList($psCategories, array $checkedList, $inputName)
    {
        $this->initCategories($psCategories);

        return $this->getCategoryTree($checkedList, $inputName);
    }

    /**
     * @param array $checkedList
     * @param string $inputName
     * @return string
     */
    public function getCategoryTree(array $checkedList, $inputName)
    {
        return <<<HTML
            <div class="yandex-money-market-category-tree-block">
                {$this->htmlControlsPanel()}
                {$this->htmlTreeCat($checkedList, $inputName)}
            </div>
HTML;
    }

    /**
     * @param array $checkedList
     * @param string $inputName
     * @return string
     */
    public function htmlTreeCat(array $checkedList, $inputName)
    {
        $html = $this->htmlTreeFolder($this->categories, self::ROOT_CATEGORY_ID, $checkedList, $inputName);

        return $html;
    }

    /**
     * @param array $categories
     * @param string $id
     * @param array $checkedList
     * @param string $inputAttr
     * @return string
     */
    private function htmlTreeFolder($categories, $id, $checkedList, $inputAttr)
    {
        if (!isset($categories[$id])) {
            return '';
        }

        $className = $id === self::ROOT_CATEGORY_ID
            ? 'yandex-money-market-category-tree-trunk'
            : 'yandex-money-market-category-tree-branch';

        $html = '<ul class="'.$className.'">';
        foreach ($categories[$id] as $categoryId => $category) {
            $checked = in_array($categoryId, $checkedList) ? ' checked' : '';
            $html    .= '<li>
                <label>
                    <input type="checkbox" '.$inputAttr.' value="'.$categoryId.'" '.$checked.'>
                    '.$category['infos']['name'].'
                </label>';
            if (isset($categories[$categoryId]) && !empty($categories[$categoryId])) {
                $html .= $this->htmlTreeFolder($categories, $categoryId, $checkedList, $inputAttr);
            }
            $html .= '</li>';
        }
        $html .= '</ul>';

        return $html;
    }

    /**
     * @return string
     */
    public function htmlControlsPanel()
    {
        $html = <<<HTML
            <div class="yandex-money-market-category-tree-panel-heading-controls clearfix">
                <div class="yandex-money-market-category-tree-actions pull-right">
                    <a onclick="return false;" class="btn btn-default market-collapse-all-category-box">
                        {$this->l($this->langCollapseAll)}
                    </a>
                    <a onclick="return false;" class="btn btn-default market-expand-all-category-box">
                        {$this->l($this->langExpandAll)}
                    </a>
                    <a onclick="return false;" class="btn btn-default market-check-all-category-box">
                        {$this->l($this->langCheckAll)}
                    </a>
                    <a onclick="return false;" class="btn btn-default market-uncheck-all-category-box">
                        {$this->l($this->langUncheckAll)}
                    </a>
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
        return 'YA_MARKET_CATEGORY_';
    }
}