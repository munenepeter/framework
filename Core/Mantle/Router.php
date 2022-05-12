<?php

namespace Chungu\Core\Mantle;

class Router {
    public $routes = [

        'GET' => [],
        'POST' => []
    ];

    public static function load($file) {

        $router = new static;
        require $file;
        return $router;
    }


    public function get($uri, $controller) {

        $this->routes['GET'][$uri] = $controller;
    }

    public function post($uri, $controller) {

        $this->routes['POST'][$uri] = $controller;
    }

    public function direct($uri, $requestType) {



        //check if the passed argument is callable
        // if (!array_key_exists($uri, $this->routes[$requestType]))
        //     throw new \Exception("Oops, Seems you're lost, There is no such page! <b>/{$uri}</b>", 404);
        // exit;
        if (!isset($this->routes[$requestType][$uri]))
            throw new \Exception("Oops, you forgot to include <b>/{$uri}</b>, There is no such route! ", 404);

        if (!is_callable($this->routes[$requestType][$uri])) {

            if (array_key_exists($uri, $this->routes[$requestType])) {
                //check if the route exists
                return $this->callAction(
                    ...explode('@', $this->routes[$requestType][$uri])
                );
            }

            throw new \Exception("Oops, you forgot to include <b>/{$uri}</b>, There is no such route! ", 404);
            exit;
        }

        $this->routes[$requestType][$uri]();
    }
    protected function callAction($controller, $action) {

        $controller = "Chungu\\Controllers\\{$controller}";

        if (!class_exists($controller)) {
            throw new \Exception("Class <b>$controller</b> doesn't not exist!", 500);
        }

        $controller = new $controller;

        $name = get_class($controller);

        if (!method_exists($controller, $action)) {

            throw new \Exception("{$name} doesn't not respond to {$action} Method!", 500);
        }

        return $controller->$action();
    }
}
