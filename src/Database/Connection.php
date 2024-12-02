<?php

namespace Tabel\Database;

use Illuminate\Database\Capsule\Manager as Capsule;

abstract class Connection {
    private static $sqlite_database = APP_ROOT . "/database/database.sqlite";

    public static function make(array $config, Capsule $capsule): void {
        try {
            if ($config['connection'] === 'sqlite') {
                if (!file_exists(self::$sqlite_database)) {
                    touch(self::$sqlite_database);
                }
                
                $capsule->addConnection([
                    "driver" => $config['connection'],
                    'database' => self::$sqlite_database,
                ]);
            } else {
                $capsule->addConnection([
                    "driver" => $config['connection'],
                    "host" => $config['host'],
                    "database" => $config['database'],
                    "username" => $config['username'],
                    "password" => $config['password'],
                    "charset" => $config['charset'] ?? 'utf8mb4',
                    "collation" => $config['collation'] ?? 'utf8mb4_unicode_ci',
                    "prefix" => $config['prefix'] ?? '',
                    "port" => $config['port'] ?? 3306,
                ]);
            }

            // Make this Capsule instance available globally
            $capsule->setAsGlobal();

            // Setup the Eloquent ORM
            $capsule->bootEloquent();
            
        } catch (\Exception $e) {
            if (php_sapi_name() === 'cli') {
                echo "Database Connection Error: " . $e->getMessage() . "\n";
                exit(1);
            } else {
                abort($e->getMessage(), (int)$e->getCode());
            }
        }
    }
}
