<?php

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

$router->get('api/customers', 'ApiController@customers');

//Can't work
//$router->post('test', 'PagesController@testpost');
