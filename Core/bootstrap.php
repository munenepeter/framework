<?php

use App\Core\Mantle\App;
use App\Core\Database\Connection;
use App\Core\Database\QueryBuilder;

define('ENV', 'production');

//require all files here
require 'helpers.php';

require_once __DIR__ . '/../vendor/autoload.php';

//configure config to always point to config.php
App::bind('config', require 'config.php');

/**
 *Bind the Database credentials and connect to the app
 *Bind the requred database file above to 
 *an instance of the connections
 */

session_start();

App::bind('database', new QueryBuilder(
    Connection::make(App::get('config')['sqlite'])
));
