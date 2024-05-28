<?php

namespace Tabel\Core;

use Tabel\Core\Cache;
use Tabel\Modules\Logger;



class Config {
    protected static $env_file = APP_ROOT . '.env';
    protected static $cache_key = 'config_cache';
    protected static $config = [];


    public static function load(): array {

        // Check if cached config exists or if the env file was modified
        $cachedConfig = Cache::get(self::$cache_key);

        if ($cachedConfig !== null && self::hasEnvFileChanged($cachedConfig)) {
            Cache::forget(self::$cache_key);
            return $cachedConfig;
        }

        // If not, load and parse the configuration file
        $config = self::parseFile();
        $config['hash'] = hash_file('sha256', self::$env_file);

        Cache::put(self::$cache_key, $config);

        return $config;
    }



    private static function checkEnvFile() {
        //check if the file exists & is readable
        if (!is_readable(self::$env_file)) {
            Logger::Debug("Config: Seems like the env file at " . self::$env_file . " is missing");
            Logger::Info("Config: Attempting to copy from the default .env.example....");
            //if not available, copy the ENV.EXAMPLE
            if (!copy(from: APP_ROOT . '.env.example', to: self::$env_file)) {
                Logger::Error("Config: Can't copy the env.example, is it missing?");
                return false;
            }
        }
        return true;
    }

    protected static function parseFile() {

        $config = [];

        if (!self::checkEnvFile()) {
            throw new \Exception("Error Processing ENV file", 500);
        }

        $envLines = file(self::$env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($envLines as $line) {
            // Check if the line contains an underscore
            if (strpos($line, '_') !== false) {
                [$parent, $childWithValue] = explode('_', $line, 2);

                // Check if the second part contains an equal sign
                if (strpos($childWithValue, '=') !== false) {
                    [$child, $value] = explode('=', $childWithValue, 2);

                    // Assign the value to the config array
                    $config[strtolower($parent)][strtolower($child)] = $value;
                }
            }
        }

        foreach ($config as $parent => $childArray) {
            foreach ($childArray as $child => $value) {
                $config[$parent][$child] = self::replaceVariables($value, $config);
            }
        }

        return $config;
    }

    protected static function replaceVariables($value, $config) {

        return preg_replace_callback(
            '/\${(.*?)}/',
            function ($matches) use ($config) {
                $variablePath = strtolower($matches[1]);
                $variablePathParts = explode('_', $variablePath);

                $currentValue = $config;

                foreach ($variablePathParts as $part) {
                    $part = strtolower($part);
                    if (isset($currentValue[$part])) {
                        $currentValue = $currentValue[$part];
                    } else {
                        return '';
                    }
                }

                return $currentValue;
            },
            $value
        );
    }
    public static function hasEnvFileChanged($config): bool {
        if ($config === null) {
            Logger::Info("Config: Caching ENV file...");
            return true;
        }
        $current_env_hash = hash_file('sha256', self::$env_file);
        $previous_env_hash = $config['hash'];

        if ($current_env_hash !== $previous_env_hash) {
            Logger::Info("Config: ENV file has been modified, refreshing the config cache");
            return true;
        }
        return false;
    }
}
