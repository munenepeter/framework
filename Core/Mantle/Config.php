<?php

namespace Tabel\Core\Mantle;

use Tabel\Core\Mantle\Cache;

class Config {
    protected static $env_file = APP_ROOT.'.env';
    protected static $cache_key = 'config_cache';


    public static function load(): array{
        // Check if cached config exists
        $cachedConfig = Cache::get(self::$cache_key);
        if ($cachedConfig !== null) {
            return $cachedConfig;
        }

        // If not, load and parse the configuration file
        $config = self::parseFile();

        // Cache the config for future use
        Cache::put(self::$cache_key, $config);

        return $config;
    }

    private static function checkEnvFile(){
        //check if the file exists & is readable
        if(!is_readable(self::$env_file)){
            Logger::Debug("Config: Seems like the env file at ". self::$env_file." is missing");
            Logger::Info("Config: Attempting to copy from the deafult .env.example....");
            //if not available, copy the ENV.EXAMPLE
            if(!copy(from: APP_ROOT.'.env.example', to: self::$env_file)){
                Logger::Error("Config: Can't copy the env.example, is it missing?");
                return false;
            } 
        }
        return true;
    }

    protected static function parseFile(){

        $config = [];

        if(!self::checkEnvFile()){
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
}
