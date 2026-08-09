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
     * Current group attributes (prefix, middleware, etc.)
     * @var array
     */
    protected static $groupStack = [];

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
     * Create a route group with shared attributes
     * 
     * @param array $attributes Group attributes (prefix, etc.)
     * @param callable $callback Route definitions
     */
    public static function group(array $attributes, callable $callback) {
        // Store current prefix
        $previousPrefix = static::getLastGroupPrefix();

        // Build new prefix
        $prefix = '';
        if (isset($attributes['prefix'])) {
            $prefix = $previousPrefix . '/' . trim($attributes['prefix'], '/');
        } else {
            $prefix = $previousPrefix;
        }

        // Store group data
        static::$groupStack[] = ['prefix' => $prefix];

        // Execute the callback
        $callback();

        // Remove the group from stack
        array_pop(static::$groupStack);
    }

    /**
     * Set a prefix for subsequent route definitions
     * 
     * @param string $prefix The prefix to prepend to routes
     * @param callable $callback Route definitions
     */
    public static function prefix(string $prefix, callable $callback) {
        static::group(['prefix' => $prefix], $callback);
    }

    /**
     * Get the current group prefix
     * 
     * @return string
     */
    protected static function getLastGroupPrefix(): string {
        if (empty(static::$groupStack)) {
            return '';
        }
        $lastGroup = end(static::$groupStack);
        return $lastGroup['prefix'] ?? '';
    }

    /**
     * Build the full URI with prefix
     * 
     * @param string $uri
     * @return string
     */
    protected static function buildUri(string $uri): string {
        $prefix = ltrim(static::getLastGroupPrefix(), '/');
        $uri = trim($uri, '/');

        if ($prefix === '') {
            return $uri;
        }

        if ($uri === '') {
            return $prefix;
        }

        return $prefix . '/' . $uri;
    }

    /**
     * Defines routes for a resource using a class name
     * 
     * @param string $uri URI of the resource
     * @param string $class Name of the controller class for the resource
     */
    public static function resource(string $uri, string $class) {
        $uri = static::buildUri($uri);

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
     * @param string|callable|array $controller Controller and action separated by "@" symbol
     */
    public static function get(string $uri, string|callable|array $controller) {
        $uri = static::buildUri($uri);
        $uri = preg_replace('/{[^}]+}/', '(.+)', $uri);
        static::$routes['GET'][$uri] = $controller;
    }

    /**
     * Defines a POST route
     * 
     * @param string $uri URI for the route
     * @param string|callable|array $controller Controller and action separated by "@" symbol
     */
    public static function post(string $uri, string|callable|array $controller) {
        $uri = static::buildUri($uri);
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
            $controller = "App\\Controllers\\{$controller}";
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
