<?php

namespace Tabel\Core\Mantle;

class App {
    public static $registry = [];

    public static function bind($key, $value) {
        self::$registry[$key] = $value;
    }

    public static function get($key) {
        $keys = explode('.', $key);
        $firstKey = $keys[0];

        if (!array_key_exists($firstKey, static::$registry)) {
            throw new \Exception("The {$firstKey} was not found in this container", 500);
        }

        $value = self::$registry[$firstKey];

        foreach (array_slice($keys, 1) as $nestedKey) {
            if (is_array($value) && array_key_exists($nestedKey, $value)) {
                $value = $value[$nestedKey];
            } else {
                throw new \Exception("The nested key {$nestedKey} was not found in the array", 500);
            }
        }

        return $value;
    }
}
