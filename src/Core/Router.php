<?php

namespace Tabel\Core;

use Tabel\Modules\Logger;

/**
 * Class responsible for routing requests to controllers and actions
 */
class Router {


    /**
     * @var array <string, array<string, string>> Associative array with request method (string) as key and array of routes (string => string) as value
     */
    public static $routes = [
        'GET' => [],
        'POST' => []
    ];
    /**
     * Loads routes from a file
     * 
     * @param string $file Path to the route definition file
     * @return self Instance of Router class
     */
    public static function load(string $file) {

        $router = new static;
        require $file;
        return $router;
    }
    /**
     * Defines routes for a resource using a class name
     * 
     * @param string $uri URI of the resource
     * @param string $class Name of the controller class for the resource
     */
    public static function resource(string $uri, string $class) {
        //index //get
        static::get($uri, "{$class}@index");
        //index //post //create
        static::post($uri, "{$class}@create");
        //index //get //view one
        static::get("$uri/{id}", "{$class}@show");
        //index //post //update one
        static::post("$uri/update/{id}", "{$class}@update");
        //index //post //delete one
        static::post("$uri/delete/{id}", "{$class}@delete");
    }
    /**
     * Defines a GET route
     * 
     * @param string $uri URI for the route
     * @param string|callable|array $controller Controller and action separated by "@" symbol (e.g. UserController@index)
     */
    public static function get(string $uri, string|callable|array $controller) {
        $uri = preg_replace('/{[^}]+}/', '(.+)', $uri);
        static::$routes['GET'][$uri] = $controller;
    }
    /**
     * Defines a POST route
     * 
     * @param string $uri URI for the route
     * @param string|callable|array $controller Controller and action separated by "@" symbol (e.g. UserController@index)
     */
    public static function post(string $uri, string|callable|array $controller) {
        $uri = preg_replace('/{[^}]+}/', '(.+)', $uri);
        static::$routes['POST'][$uri] = $controller;
    }
    /**
     * Calls a route based on uri and request type
     * 
     * @param string $uri URI of the request
     * @param string $requestType HTTP request method (e.g. GET, POST)
     * @return mixed Can return any type depending on the called route
     */
    public function direct(string $uri, string $requestType): mixed {

        $params = [];
        $regexUri = '';

        foreach (Router::$routes[$requestType] as $route => $controller) {
            if (preg_match("%^{$route}$%", $uri, $matches) === 1) {
                $regexUri = $route;
                Router::$routes[$requestType][$regexUri] = $controller;

                unset($matches[0]);
                $params = $matches;
                break;
            }
        }


        if (is_callable(Router::$routes[$requestType][$regexUri])) {
            return Router::$routes[$requestType][$regexUri](...$params);
        }
        if (!array_key_exists($uri, Router::$routes[$requestType])) {
            throw new \Exception("There is no handler for <b>" . strtoupper($requestType) . " /{$uri}</b> ", 404);
        }
        if (is_array(Router::$routes[$requestType][$regexUri])) {
            return $this->callAction(
                $params,
                ...Router::$routes[$requestType][$regexUri]
            );
        }
        if (!empty($regexUri) && $regexUri !== "") {
            return $this->callAction(
                $params,
                ...explode('@', Router::$routes[$requestType][$regexUri])
            );
        } else {
            return $this->callAction(
                $params,
                ...explode('@', Router::$routes[$requestType][$uri])
            );
        }
    }
    /**
     * Calls a controller action
     * 
     * @param array $params Route parameters as an array
     * @param string $controller Controller class name
     * @param string $action Action method name within the controller
     * @return mixed Can return any type depending on the controller action
     */
    protected function callAction(array $params, string $controller, string $action): mixed {

        if (strpos($controller, "\\") === false) {
            $controller = "Tabel\\Controllers\\{$controller}";
        }

        if (!class_exists($controller)) {
            Logger::Error("Class {$controller} does not exist!");
            throw new \Exception("Class <b>$controller</b> doesn't not exist!", 500);
        }

        $controller = new $controller;

        $name = get_class($controller);

        if (!method_exists($controller, $action)) {
            Logger::Error("Method {$action} does not exist on the {$name} class");
            throw new \Exception("{$name} doesn't not respond to {$action} Method!", 500);
        }

        return $controller->$action(...$params);
    }
}
