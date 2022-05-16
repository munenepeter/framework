<?php

use Babel\Controllers\PagesController;
use Babel\Core\Mantle\Validator;
//get routes

$router->get('index', 'PagesController@index');
$router->get('', 'PagesController@index');

$router->get('test', function () {
    return view('test');
});

$router->post('test', function () {

    $validator =  new Validator();

    $e = $validator->validate($_POST, [
        'username' => 'required',
        'email' => 'required|email',
        'password' => 'required|secure',
    ]);

    return view('test', ['e' => $e]);
});

//Can't work
//$router->post('test', 'PagesController@testpost');
