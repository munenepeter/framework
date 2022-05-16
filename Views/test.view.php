<?php

use Babel\Core\Mantle\Validator;

 
 

echo '<pre>';

$data = [
    'firstname' => '',
    'username' => '8h',
    'lastname' => 'test',
    'address' => '',
    'zipcode' => '999',
    'email' => 'jo@',
    'password' => '',
    'password2' => 'test',
];



$fields = [
    'firstname' => 'required',
    'lastname' => 'required',
    'address' => 'required | min: 10, max:255',
    'zipcode' => 'between: 5,6',
    'username' => 'required | alphanumeric | between: 3,255 ',
    'email' => 'required | email',
    'password' => 'required | secure',
    'password2' => 'required | same:password'
];


$validator =  new Validator();
print_r($validator->validate($data, $fields));
echo '<br>';
// $errors = validate($data, $fields);

// print_r($errors);
