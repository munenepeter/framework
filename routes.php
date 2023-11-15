<?php

use Tabel\Core\Mantle\Router;

$router->get('', 'PagesController@index');



//logs
$router->get(':system:/logs', 'SystemController@index');
$router->post(':system:/logs/delete', 'SystemController@deleteLogs');
//robots
$router->get('robots.txt', function () {
    return require __DIR__ . "/robots.txt";
});