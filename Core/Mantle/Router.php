<?php
namespace App\Core\Mantle;

class Router{
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

        if (array_key_exists($uri, $this->routes[$requestType])) {

            return $this->callAction(
                ...explode('@', $this->routes[$requestType][$uri])
            );
        }

        throw new \Exception("There are no defined routes for this URI <b>/{$uri}</b>", 501);
    }
    protected function callAction($controller, $action) {

        $controller = "App\\Controllers\\{$controller}";

        $controller = new $controller;

        $name = get_class($controller);

        if (!method_exists($controller, $action)) {

            throw new \Exception("{$name} doesn't not respond to {$action} Method!", 500);
        }

        return $controller->$action();
    }
}
