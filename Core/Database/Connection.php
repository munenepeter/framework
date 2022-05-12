<?php

namespace App\Core\Database;

class Connection{
    //make a connection to the DB
    public static function make($config) {

        try {
            /* fall back code ie the config is absent
            return new \PDO("sqlite:" . Config::PATH_TO_SQLITE_FILE);*/

            //Here you return the credentials stored in the config file
            return new \PDO("sqlite:" . $config['path']);

        } catch (\PDOException $e) {
            //if anything happens throw an error
            abort($e->getMessage(), (int)$e->getCode());
        }
    }
}