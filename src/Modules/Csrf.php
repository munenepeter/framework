<?php

namespace Tabel\Modules;

/**
 * CSRF protection for state-changing requests.
 *
 * Opt in with APP_CSRF=true in .env. When on, the Router rejects any POST / PUT / PATCH /
 * DELETE that does not carry the session token, either as a `_csrf` form field
 * (use csrf_field() in forms) or an `X-CSRF-TOKEN` header (use csrf_token() for fetch / AJAX).
 * Requests authenticated with an `Authorization: Bearer ...` header are exempt, since a
 * browser cannot be tricked into attaching that header cross-site.
 */
class Csrf {

    public const FIELD = '_csrf';
    public const HEADER = 'HTTP_X_CSRF_TOKEN';
    private const SESSION_KEY = '_csrf';

    /** Force on/off (tests); null = read APP_CSRF. */
    public static ?bool $override = null;

    public static function enabled(): bool {
        if (self::$override !== null) {
            return self::$override;
        }
        try {
            $value = app()->get('config.app.csrf');
        } catch (\Throwable $e) {
            $value = null;
        }
        $value ??= getenv('APP_CSRF') ?: null;
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function shouldVerify(string $method): bool {
        if (!in_array(strtoupper($method), ['POST', 'PUT', 'PATCH', 'DELETE'], true) || !self::enabled()) {
            return false;
        }
        return !str_starts_with((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''), 'Bearer ');
    }

    /** The session's token (created on first use). */
    public static function token(): string {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::SESSION_KEY];
    }

    public static function field(): string {
        return '<input type="hidden" name="' . self::FIELD . '" value="'
            . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function valid(?string $sent): bool {
        $expected = $_SESSION[self::SESSION_KEY] ?? '';
        return is_string($sent) && $sent !== '' && $expected !== '' && hash_equals($expected, $sent);
    }

    /** Throws a 419 when the request's token is missing or wrong. */
    public static function verify(): void {
        $sent = $_POST[self::FIELD] ?? $_SERVER[self::HEADER] ?? null;
        if (!self::valid(is_string($sent) ? $sent : null)) {
            if (function_exists('logger')) {
                logger('Warning', 'CSRF token mismatch on ' . ($_SERVER['REQUEST_URI'] ?? ''));
            }
            throw new \RuntimeException('Your session expired or the form is invalid. Go back, refresh the page and try again.', 419);
        }
    }
}
