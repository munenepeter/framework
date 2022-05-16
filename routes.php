<?php
//get routes

$router->get('index', 'PagesController@index');
$router->get('', 'PagesController@index');

$router->get('test', function () {
    return view('test');
});
