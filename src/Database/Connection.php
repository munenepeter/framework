<?php

namespace Tabel\Core\Database;

use Illuminate\Database\Capsule\Manager as Capsule;


class Connection {

    private static $sqlite_database = APP_ROOT . "Core/Database/sqlite/database.sqlite";
    public static function make(array $config, Capsule $capsule): void {
        try {
            if ($config['connection'] === 'sqlite') {
                $capsule->addConnection([
                    "driver" => $config['connection'],
                    'database' => self::$sqlite_database,
                ]);
            }
            $capsule->addConnection([
                "driver" => $config['connection'],
                "host" => $config['host'],
                "database" => $config['database'],
                "username" => $config['username'],
                "password" => $config['password']
            ]);
        } catch (\Exception $e) {
            abort($e->getMessage(), (int)$e->getCode());
        }
    }
}
