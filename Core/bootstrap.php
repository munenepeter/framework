<?php

use Tabel\Core\Mantle\App;
use Tabel\Core\Database\Connection;
use Tabel\Core\Database\QueryBuilder;
use Tabel\Core\Mantle\Config;

//change TimeZone
date_default_timezone_set('Africa/Nairobi'); 
//production development
define('ENV','development');
define('APP_ROOT', __DIR__."/../");

//require all files here
require 'helpers.php';

require_once __DIR__.'/../vendor/autoload.php';

//configure config to always point to env
App::bind('config', Config::load()); 

//print_r(App::get('config'));

session_start();

/**
 *Bind the Database credentials and connect to the app
 *Bind the requred database file above to 
 *an instance of the connections
*/

App::bind('database', new QueryBuilder(
    Connection::make(App::get('config')['db'])
));
