<?php

return [
    //Get the configuration details ie DB connections
    'mysqldatabase' => [
        'name' => 'test',
        'username' => 'root',
        'password' => '',
        'connection' => 'mysql:host=localhost',
        'options' => [
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
        ],
        
         'sqlite' =>[
           'path' => 'path/to/sqlite/database.sqlite'
         ]         
        ];
