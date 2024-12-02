<?php

namespace Tabel\Core;

class Cache {

    /**
     * Retrieve a cached value by key
     * 
     * @param string $key Cache key
     * @return mixed|null Cached value or null if not found
     */
    public static function get($key) {
        $filePath = self::getFilePath($key);

        if (file_exists($filePath)) {
            $contents = file_get_contents($filePath);
            if ($contents === false) {
                logger("Error", "Failed to read cache file for key: {$key}");
                return null;
            }

            $data = unserialize($contents);
            if ($data === false) {
                logger("Error", "Failed to unserialize cache data for key: {$key}");
                return null;
            }

            if ($data['expiration'] > time() || $data['expiration'] === 0) {
                return $data['value'];
            } else {
                self::forget($key); // delete expired cache file
            }
        }

        return null;
    }

    /**
     * Store a value in the cache
     * 
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $minutes Expiration time in minutes
     * @return void
     */
    public static function put($key, $value, $minutes = 60) {
        $expiration = ($minutes > 0) ? time() + ($minutes * 60) : 0;
        $data = [
            'value' => $value,
            'expiration' => $expiration,
        ];

        $filePath = self::getFilePath($key);
        $contents = serialize($data);

        if (file_put_contents($filePath, $contents) === false) {
            logger("Error", "Failed to write cache file for key: {$key}");
        }
    }

    /**
     * Remove a cached value by key
     * 
     * @param string $key Cache key
     * @return void
     */
    public static function forget($key) {
        $filePath = self::getFilePath($key);

        if (file_exists($filePath) && !unlink($filePath)) {
            logger("Error", "Failed to delete cache file for key: {$key}");
        }
    }

    /**
     * Generate the file path for a cache key
     * 
     * @param string $key Cache key
     * @return string File path
     */
    protected static function getFilePath($key) {
        $cacheDirectory = APP_ROOT . '/storage/cache/';
        if (!is_dir($cacheDirectory) && !mkdir($cacheDirectory, 0777, true) && !is_dir($cacheDirectory)) {
            logger("Error", "Failed to create cache directory: {$cacheDirectory}");
            throw new \RuntimeException("Failed to create cache directory");
        }
        return $cacheDirectory . md5($key);
    }
}
