<?php

return [
  //Get the configuration details ie DB connections
  //when using MYSQL
  'mysql' => [
    'name' => 'clive',
    'username' => '',
    'password' => '',
    'connection' => 'mysql:host=localhost',
    'options' => [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]
  ],
  //when using SQLITE
  'sqlite' => [
    'path' => 'Core/Database/babel.sqlite'
  ],
  'codes' => [
    403 => ['403 Forbidden', 'The server refused to fulfill the request.'],
    404 => ['404 Not Found', 'The page requested was not found on this server.'],
    405 => ['405 Method Not Allowed', 'The method specified in the Request-Line is not allowed for the specified resource.'],
    408 => ['408 Request Timeout', 'Your browser failed to send a request in the time allowed by the server.'],
    500 => ['500 Internal Server Error', 'The request was unsuccessful due to an unexpected condition encountered by the server.'],
    502 => ['502 Bad Gateway', 'The server received an invalid response from the upstream server while trying to fulfill the request.'],
    504 => ['504 Gateway Timeout', 'The upstream server failed to send a request in the time allowed by the server.'],
    2002 => ['SQLSTATE[HY000] No connection', 'Can\'t connect to a MySQL server or Database']
  ]
];
