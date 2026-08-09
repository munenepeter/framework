<?php

namespace Tabel\Modules;

use Tabel\Core\Request;

/**
 * Writes application logs in a Laravel-style single-line format:
 *
 *   [2026-08-09 12:34:56] production.ERROR: Something went wrong {"method":"GET","uri":"/foo","ip":"1.2.3.4","agent":"..."}
 *
 * Each entry is kept to a single line
 */
class Logger {

    protected static $logBuffer = [];
    public static string $logFile = \APP_ROOT . "/storage/logs/tabel.log";

    public static function log(string $level, string $msg) {
        $time = date('Y-m-d H:i:s');
        $env = self::environment();
        $levelName = strtoupper($level);

        // remove newlines so each log entry stays on one line
        $message = str_replace(["\r\n", "\n", "\r"], ' ', $msg);

        $context = self::buildContext();
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';

        $line = "[{$time}] {$env}.{$levelName}: {$message}{$contextStr}";

        self::$logBuffer[] = $line;
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

    /**
     * Resolve the current environment name for the log channel prefix,
     * e.g. "production", "local".
     */
    protected static function environment(): string {
        if (defined('APP_ENV')) {
            return \APP_ENV;
        }

        $env = getenv('APP_ENV');
        return $env !== false && $env !== '' ? $env : 'production';
    }

    /**
     * Build request context for non-CLI requests. CLI runs omit this
     * entirely since there's no HTTP request to describe.
     */
    protected static function buildContext(): array {
        if (Request::isCli()) {
            return [];
        }

        return [
            'method' => Request::method(),
            'uri' => '/' . Request::uri(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'N/A',
            // 'agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'N/A',
        ];
    }

    protected static function writeBufferToFile() {
        if (!file_exists(self::$logFile)) {
            mkdir(\APP_ROOT . "/storage/logs", 0755, true);
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

        $logs = explode(PHP_EOL, $data);

        //remove trailing empty line
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
        logger("Info", "System: Logs have been deleted by {$user} at " . date('Y-m-d H:i:s'));
        fclose($newLogFile);

        return true;
    }
}
