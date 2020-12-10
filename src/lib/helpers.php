<?php

/**
 * @param array $array
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function yoomoney_array_get($array, $key, $default = null)
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
function yoomoney_get_settings($settings, $key, $index = null, $default = null)
{
    return $index === null
        ? yoomoney_array_get($settings, $key)
        : yoomoney_array_get(
            yoomoney_array_get($settings, $key, array()),
            $index,
            $default
        );
}