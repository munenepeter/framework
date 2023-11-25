<?php

use Tabel\Core\Mantle\App;
use Tabel\Core\Database\Connection;
use Tabel\Core\Database\QueryBuilder;
use Tabel\Core\Mantle\Config;
use Tabel\Core\Mantle\Mail;

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


session_start();

// Bind the Database credentials and connect to the app
App::bind('database', new QueryBuilder(
    Connection::make(App::get('config.db'))
));

// Bind the Database credentials and connect to the app
App::bind('mailer', new Mail(App::get('config.mail')));
