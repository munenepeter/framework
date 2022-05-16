<?php

use Babel\Core\Mantle\Validator;
//get routes

$router->get('index', 'PagesController@index');
$router->get('', 'PagesController@index');

$router->get('test', function () {
    return view('test');
});

$router->post('test', function () {

    $fields = [
        'name' => 'required',
        'email' => 'required | email',
        'password' => 'required | secure',
    ];


    $validator =  new Validator();
    $e = $validator->validate($_POST, $fields);

    return view('test', ['e' => $e]);
});
