App<?php

use App\Core\Mantle\Router;
use App\Core\Mantle\Request;

//require the main file joining all the parts of the app
require 'Core/bootstrap.php';

//Try to load the routes, direct the URI and check the request method
 try {

    Router::load('routes.php')->direct(Request::uri(), Request::method());

} catch (\Exception $e) {

    //Instead of catching the exception here we redirect the same to our main error handler
    abort($e->getMessage(), $e->getCode());
}
