<?php

namespace Tabel\Core\Database;

class Connection {

    private static $sqlite_database = APP_ROOT."Core/Database/sqlite/db.sqlite";
    //make a connection to the DB
    public static function make($config) {

        try {

            if($config['connection'] === 'sqlite'){
                return new \PDO("sqlite:".self::$sqlite_database);
            }
                return new \PDO(
                    $config['connection'] . ';dbname=' . $config['name'] .';charset=utf8mb4',
                    $config['username'],
                    $config['password'],
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
        } catch (\PDOException $e) {
            //if anything happens throw an error
            abort($e->getMessage(), (int)$e->getCode());
        }
    }
}
