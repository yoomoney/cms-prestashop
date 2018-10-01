<?php

/**
 * @param array $array
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function ym_array_get($array, $key, $default = null)
{
    return isset($array[$key]) ? $array[$key] : $default;
}

/**
 * @param array $settings
 * @param string $key
 * @param mixed $index
 * @param null $default
 * @return mixed
 */
function ym_get_settings($settings, $key, $index = null, $default = null)
{
    if (in_array($key, array(
            'YA_MARKET_CURRENCY_ENABLED',
            'YA_MARKET_CURRENCY_RATE',
            'YA_MARKET_CURRENCY_PLUS',
            'YA_MARKET_CATEGORY_LIST',
            'YA_MARKET_DELIVERY_ENABLED',
            'YA_MARKET_DELIVERY_COST',
            'YA_MARKET_DELIVERY_DAYS_FROM',
            'YA_MARKET_DELIVERY_DAYS_TO',
            'YA_MARKET_DELIVERY_ORDER_BEFORE',
            'YA_MARKET_AVAILABLE_ENABLED',
            'YA_MARKET_AVAILABLE_AVAILABLE',
            'YA_MARKET_AVAILABLE_DELIVERY',
            'YA_MARKET_AVAILABLE_PICKUP',
            'YA_MARKET_AVAILABLE_STORE',
            'YA_MARKET_VAT_LIST',
            'YA_MARKET_ADDITIONAL_CONDITION_ENABLED',
            'YA_MARKET_ADDITIONAL_CONDITION_NAME',
            'YA_MARKET_ADDITIONAL_CONDITION_TAG',
            'YA_MARKET_ADDITIONAL_CONDITION_TYPE_VALUE',
            'YA_MARKET_ADDITIONAL_CONDITION_STATIC_VALUE',
            'YA_MARKET_ADDITIONAL_CONDITION_DATA_VALUE',
            'YA_MARKET_ADDITIONAL_CONDITION_FOR_ALL_CAT',
            'YA_MARKET_ADDITIONAL_CONDITION_JOIN',
            'YA_MARKET_ADDITIONAL_CONDITION_CATEGORIES',
        ))
        && isset($settings[$key])
    ) {
        $settingsKey = json_decode($settings[$key], true);
        if ($index === null) {
            return $settingsKey;
        }

        return ym_array_get(
            $settingsKey,
            $index,
            $default
        );
    }


    return $index === null
        ? ym_array_get($settings, $key)
        : ym_array_get(
            ym_array_get($settings, $key, array()),
            $index,
            $default
        );
}