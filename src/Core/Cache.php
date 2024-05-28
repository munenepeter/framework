<?php

namespace Tabel\Core;


class Cache {

    public static function get($key) {
        $filePath = self::getFilePath($key);

        if (file_exists($filePath)) {
            $contents = file_get_contents($filePath);
            $data = unserialize($contents);

            if ($data['expiration'] > time() || $data['expiration'] === 0) {
                return $data['value'];
            } else {
                self::forget($key); // Delete expired cache file
            }
        }

        return null;
    }

    public static function put($key, $value, $minutes = 60) {
        $expiration = ($minutes > 0) ? time() + ($minutes * 60) : 0;
        $data = [
            'value' => $value,
            'expiration' => $expiration,
        ];

        $filePath = self::getFilePath($key);
        $contents = serialize($data);

        file_put_contents($filePath, $contents);
    }

    public static function forget($key) {
        $filePath = self::getFilePath($key);

        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    protected static function getFilePath($key) {
        $cacheDirectory = APP_ROOT . '/storage/cache/';
        if (!is_dir($cacheDirectory)) {
            mkdir($cacheDirectory, 0777, true);
        }
        return $cacheDirectory . md5($key);
    }
}
