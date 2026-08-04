<?php

namespace Tabel\Database;

/**
 * Connection
 *
 * Creates and returns a raw PDO instance. Supports only MySQL/MariaDB and SQLite.
 *
 * @package Tabel\Database
 */
class Connection {

    private static string $sqliteDatabase = '';

    /**
     * Build a PDO instance from the given config array.
     *
     * Expected keys: connection, host, database, username, password,
     *                charset, collation, prefix, port
     *
     * @param  array $config
     * @return \PDO
     * @throws \RuntimeException
     */
    public static function make(array $config): \PDO {
        self::$sqliteDatabase = defined('APP_ROOT')
            ? APP_ROOT . '/database/database.sqlite'
            : getcwd() . '/database/database.sqlite';

        try {
            return match ($config['connection'] ?? 'mysql') {
                'sqlite' => self::makeSqlite(),
                default  => self::makeMysql($config),
            };
        } catch (\PDOException $e) {
            if (php_sapi_name() === 'cli') {
                echo "Database Connection Error: " . $e->getMessage() . "\n";
                exit(1);
            }
            throw new \RuntimeException(
                "Database Connection Error: " . $e->getMessage(),
                (int) $e->getCode()
            );
        }
    }

    private static function makeSqlite(): \PDO {
        if (!file_exists(self::$sqliteDatabase)) {
            $dir = dirname(self::$sqliteDatabase);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            touch(self::$sqliteDatabase);
        }

        $pdo = new \PDO('sqlite:' . self::$sqliteDatabase);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        // Enable WAL for better concurrency
        // assume user's preferences
        $pdo->exec('PRAGMA journal_mode=WAL;');

        return $pdo;
    }

    private static function makeMysql(array $config): \PDO {
        $charset   = $config['charset']   ?? 'utf8mb4';
        $port      = $config['port']      ?? 3306;
        $host      = $config['host']      ?? 'localhost';
        $database  = $config['database']  ?? '';

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";

        $pdo = new \PDO(
            $dsn,
            $config['username'] ?? '',
            $config['password'] ?? '',
            [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );

        return $pdo;
    }
}
