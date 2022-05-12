<?php

namespace App\Core\Mantle;

class Logger {
    public static function log($msg) {

        $log = date("D, d M Y H:i:s") . ' - ' . $_SERVER['SERVER_NAME'] . ' - ' . $_SERVER['REMOTE_ADDR'] . ' - ' . "$msg" . PHP_EOL;

        $logFile =  __DIR__ . "/Logs/logs.log";

        if (!file_exists($logFile)) {
            mkdir(__DIR__ . "/Logs");
        }

        $file = fopen($logFile, 'a+', 1);
        fwrite($file, $log);
        fclose($file);
    }
}
