<?php

namespace Tabel\Modules;

use Tabel\Core\Cache;
use Tabel\Core\Request;


class Logger {

    protected static $logBuffer = [];
    protected static $cachedUserInfo = null;
    public static $logFile = APP_ROOT . "/storage/logs/tabel.log";

    public static function log(string $level, string $msg) {
        $userinfo = self::getUserInfo();
        $log = [
            'id' => md5(time()),
            'level' => $level,
            'time' => date("D, d M Y H:i:s"),
            "more" => [
                "method" => Request::method(),
                "uri" => '/' . Request::uri(),
                "remote_addr" => $_SERVER['REMOTE_ADDR'],
                "region" => $userinfo->region_name ?? "N/A",
                "country" => $userinfo->country_name ?? "N/A",
                "city" => $userinfo->city ?? "N/A",
                "provider" => $userinfo->organisation ?? "N/A",
                "time_zone" => $userinfo->time_zone ?? "N/A",
                "agent" => $_SERVER['HTTP_USER_AGENT']
            ],
            "description" => nl2br($msg)
        ];
        self::$logBuffer[] = json_encode($log);
        self::writeBufferToFile();
    }

    public static function Debug(string $log) {
        self::log("Debug", $log);
    }
    public static function Info(string $log) {
        self::log("Info", $log);
    }
    public static function Error(string $log) {
        self::log("Error", $log);
    }
    public static function Warning(string $log) {
        self::log("Warning", $log);
    }

    protected static function getUserInfo() {
        if (self::$cachedUserInfo === null) {
            // Check if cached user info exists
            $cachedUserInfo = Cache::get('user_info');

            if ($cachedUserInfo !== null) {
                self::$cachedUserInfo = json_decode($cachedUserInfo);
            } else {
                // Fetch user info from API
                $response = @file_get_contents(
                    'http://ip-api.io/json/' . $_SERVER['REMOTE_ADDR'],
                    false,
                    stream_context_create([
                        'http' => [
                            'ignore_errors' => true,
                        ],
                    ])
                );

                // Cache user info for future requests
                self::$cachedUserInfo = json_decode($response);
                Cache::put('user_info', $response, 3600); // Cache for 1 hour

            }
        }

        return self::$cachedUserInfo;
    }

    protected static function writeBufferToFile() {
        if (!file_exists(self::$logFile)) {
            mkdir(APP_ROOT . "/storage/logs");
        }

        $file = fopen(self::$logFile, 'a+', 1);
        fwrite($file, implode(PHP_EOL, self::$logBuffer) . PHP_EOL);
        fclose($file);

        // Clear the buffer after writing
        self::$logBuffer = [];
    }

    public static function flush() {
        if (!empty(self::$logBuffer)) {
            self::writeBufferToFile();
        }
    }
    public static function getLogs(): array {
        $data = file_get_contents(self::$logFile);

        //separate the logs by line
        $logs = explode(PHP_EOL, $data);

        //remove 1st empty line
        array_pop($logs);
        //return latest on 1st
        return array_reverse($logs);
    }

    public static function deleteLogs(string $user): bool {
        if (!file_exists(self::$logFile)) {
            $newLogFile = fopen(self::$logFile, "w") or die("Unable to open file!");
            fclose($newLogFile);
            return false;
        }

        //delete the file and create a new one
        if (!unlink(self::$logFile)) {
            logger("Debug", "System: Couldn't delete the logs!");
            return false;
        }
        //recreate the file
        $newLogFile = fopen(self::$logFile, "w") or die("Unable to open file!");
        logger("Info", "System: Logs have been deleted by {$user}");
        fclose($newLogFile);

        return true;
    }
}
