<?php

use Babel\Core\Mantle\Validator;

echo '<pre>';

$data = [
    'firstname' => '',
    'username' => 'bob',
    'address' => 'This is my address',
    'zipcode' => '999',
    'email' => 'jo@',
    'password' => 'test123',
    'password2' => 'test',
];



$fields = [
    'firstname' => 'required, max:255',
    'lastname' => 'required, max: 255',
    'address' => 'required | min: 10, max:255',
    'zipcode' => 'between: 5,6',
    'username' => 'required | alphanumeric | between: 3,255 ',
    'email' => 'required | email',
    'password' => 'required | secure',
    'password2' => 'required | same:password'
];


$validator =  new Validator();

$errors = $validator->validate($data, $fields);

print_r($errors);