<?php

namespace Tabel\Core;

use Tabel\Core\Cache;
use Tabel\Modules\Logger;

class Config {
    protected static $env_file = APP_ROOT . '.env';
    protected static $cache_key = 'config_cache_v2';
    protected static $config = [];

    /**
     * Load configuration from cache or file
     * 
     * @return array Configuration data
     * @throws \Exception If the configuration file cannot be processed
     */
    public static function load(): array {
        $cachedConfig = Cache::get(self::$cache_key);

        if ($cachedConfig !== null && !self::hasEnvFileChanged($cachedConfig)) {
            self::populateEnv($cachedConfig);
            return $cachedConfig;
        }

        $config = self::parseFile();
        $config['hash'] = hash_file('sha256', self::$env_file);

        Cache::put(self::$cache_key, $config);
        self::populateEnv($config);

        return $config;
    }

    /**
     * Populate $_ENV superglobal with flattened config values
     * 
     * @param array $config Configuration array
     */
    private static function populateEnv(array $config): void {
        foreach ($config as $parent => $childArray) {
            if ($parent === 'hash') continue;
            
            if (is_array($childArray)) {
                foreach ($childArray as $child => $value) {
                    $envKey = strtoupper("{$parent}_{$child}");
                    $_ENV[$envKey] = $value;
                    putenv("{$envKey}={$value}");
                }
            }
        }
    }

    /**
     * Check if the environment file exists and is readable
     * 
     * @return bool True if the file is available, false otherwise
     */
    private static function checkEnvFile(): bool {
        if (!is_readable(self::$env_file)) {
            Logger::Debug("Config: Env file at " . self::$env_file . " is missing");
            Logger::Info("Config: Attempting to copy from the default .env.example...");

            if (!copy(APP_ROOT . '.env.example', self::$env_file)) {
                Logger::Error("Config: Can't copy the env.example, is it missing?");
                return false;
            }
        }
        return true;
    }

    /**
     * Parse the environment file into a configuration array
     * 
     * @return array Parsed configuration
     * @throws \Exception If the environment file cannot be processed
     */
    protected static function parseFile(): array {
        if (!self::checkEnvFile()) {
            throw new \Exception("Error Processing ENV file", 500);
        }

        $config = [];
        $envLines = file(self::$env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($envLines as $line) {
            $line = trim($line);

            // Blank lines and comments. Without this, "# DB_HOST=x" was parsed
            // as the key "# DB" and leaked into the config.
            if ($line === '' || $line[0] === '#') {
                continue;
            }

            if (strpos($line, '_') !== false) {
                [$parent, $childWithValue] = explode('_', $line, 2);

                if (strpos($childWithValue, '=') !== false) {
                    [$child, $value] = explode('=', $childWithValue, 2);
                    $config[strtolower($parent)][strtolower($child)] = self::unquote($value);
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

    /**
     * Strip one pair of matching surrounding quotes: APP_NAME="Tabel" -> Tabel.
     * The quotes used to be kept, so config('app.name') returned "\"Tabel\"".
     */
    protected static function unquote(string $value): string {
        $value = trim($value);
        $len = strlen($value);

        if ($len >= 2 && ($value[0] === '"' || $value[0] === "'") && $value[$len - 1] === $value[0]) {
            return substr($value, 1, -1);
        }
        return $value;
    }

    /**
     * Replace variables in configuration values
     * 
     * @param string $value The value to process
     * @param array $config The configuration array
     * @return string The processed value
     */
    protected static function replaceVariables($value, $config): string {
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

    /**
     * Check if the environment file has changed
     * 
     * @param array $config Cached configuration
     * @return bool True if the file has changed, false otherwise
     */
    public static function hasEnvFileChanged($config): bool {
        if ($config === null) {
            Logger::Info("Config: Caching ENV file...");
            Cache::forget('app_container');
            return true;
        }
        $current_env_hash = hash_file('sha256', self::$env_file);
        $previous_env_hash = $config['hash'];

        if ($current_env_hash !== $previous_env_hash) {
            Logger::Info("Config: ENV file has been modified, refreshing the config cache");
            Cache::forget('app_container');
            return true;
        }
        return false;
    }
}
