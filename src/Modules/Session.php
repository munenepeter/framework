<?php

namespace Tabel\Modules;

class Session {

    /**
     * Start the PHP session once, with safe cookie defaults. Safe to call repeatedly
     * (no-op when a session is already active) and in CLI (no-op).
     *
     * Options: name, lifetime, path, domain, secure, httponly, samesite.
     */
    public static function start(array $options = []): bool {
        if (PHP_SAPI === 'cli' || session_status() === PHP_SESSION_ACTIVE) {
            return session_status() === PHP_SESSION_ACTIVE;
        }
        if (headers_sent()) {
            return false;
        }

        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        if (!empty($options['name'])) {
            session_name((string) $options['name']);
        }
        session_set_cookie_params([
            'lifetime' => (int) ($options['lifetime'] ?? 0),
            'path'     => $options['path'] ?? '/',
            'domain'   => $options['domain'] ?? '',
            'secure'   => (bool) ($options['secure'] ?? $https),
            'httponly' => (bool) ($options['httponly'] ?? true),
            'samesite' => $options['samesite'] ?? 'Lax',
        ]);

        return session_start();
    }

    /** Store a value only if the key is not already set (original behaviour, kept for compatibility). */
    public static function make(mixed $name, mixed $value) {
        if (isset($_SESSION[$name])) {
            return;
        }
        return $_SESSION[$name] = $value;
    }

    /** Store a value, replacing whatever was there. */
    public static function set(mixed $name, mixed $value): mixed {
        return $_SESSION[$name] = $value;
    }

    public static function has(mixed $name): bool {
        return isset($_SESSION[$name]);
    }

    /** Append to an array value (creates it when missing). Returns the updated array. */
    public static function push(mixed $key, mixed $value): array {
        if (!isset($_SESSION[$key]) || !is_array($_SESSION[$key])) {
            $_SESSION[$key] = [];
        }
        $_SESSION[$key][] = $value;
        return $_SESSION[$key];
    }

    public static function get(mixed $name, mixed $default = null): mixed {
        return $_SESSION[$name] ?? $default;
    }

    /** Read a value and remove it (flash-style). */
    public static function pull(mixed $name, mixed $default = null): mixed {
        $value = $_SESSION[$name] ?? $default;
        unset($_SESSION[$name]);
        return $value;
    }

    public static function unset(mixed $key) {
        if (array_key_exists($key, $_SESSION ?? [])) {
            unset($_SESSION[$key]);
        }
        return;
    }

    /** Give the visitor a new session id (call after login to prevent fixation). */
    public static function regenerate(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    /** End the session: empties $_SESSION, expires the cookie and destroys the stored data. */
    public static function destroy() {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            if (ini_get('session.use_cookies') && !headers_sent()) {
                $p = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
            }
            session_destroy();
        }
        return;
    }
}
