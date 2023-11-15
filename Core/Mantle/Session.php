<?php

namespace Tabel\Core\Mantle;

class Session {

    public static function make(mixed $name, mixed $value) {
        if (isset($_SESSION[$name])) {
            return;
        }
        return $_SESSION[$name] = $value;
    }
    public static function push(mixed $key, mixed $value) {
        if (!isset($_SESSION[$key])) {
            self::make($key, []);
            $_SESSION[$key] = self::push($key, $value);
            return $_SESSION[$key];
        }
        $_SESSION[$key] = array_push($_SESSION[$key], $value);
        return $_SESSION[$key];
    }
    public static function get(mixed $name) {
        if (!isset($_SESSION[$name])) {
            return;
        }
        return $_SESSION[$name];
    }

    public static function unset(mixed $key) {
        if (array_key_exists($key, $_SESSION)) {
            unset($_SESSION[$key]);
        }
        return;
    }
    public static function destroy() {
        session_destroy();
        return;
    }
}
