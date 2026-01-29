<?php

namespace PlacetoPay\Kount\Helpers;

class ArrayHelper
{
    public static function get(array $array, string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);

        foreach ($keys as $key) {
            if (is_array($array) && array_key_exists($key, $array)) {
                $array = $array[$key];
            } else {
                return $default;
            }
        }

        return $array;
    }
}
