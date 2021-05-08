<?php

return [
    //Get the configuration details ie DB connections
    'database' => [
        'name' => 'test',
        'username' => 'root',
        'password' => '',
        'connection' => 'mysql:host=localhost',
        'options' => [
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
      ]
    ];
