<?php

/**
 * Test bootstrap
 *
 * Sets up a minimal environment so the framework classes can be loaded
 * without a full HTTP context.  Tests use an in-memory SQLite database
 * so they are completely self-contained and leave no files on disk.
 */

// APP_ROOT must be defined before autoload.files (helpers.php) loads,
// because Logger.php uses APP_ROOT at the class-property level.
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Stub helpers BEFORE autoload so helpers.php's function_exists guards
// defer to these no-op versions, preventing Logger from writing to disk.
if (!function_exists('logger')) {
    function logger(string $level, string $message): void {
        // intentional no-op in tests
    }
}

if (!function_exists('auth')) {
    function auth(): false {
        return false;
    }
}

if (!function_exists('app')) {
    function app(): never {
        throw new \LogicException(
            'app() called in tests - inject dependencies directly instead.'
        );
    }
}

require_once __DIR__ . '/../vendor/autoload.php';
