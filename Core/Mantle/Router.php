<?php

namespace Babel\Core\Mantle;

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
        $params = [];
        $regexUri = '';
        foreach ($this->routes[$requestType] as $route => $controller) {
            if (preg_match("%^{$route}$%", $uri, $matches) === 1) {
                // var_dump($matches);
                $regexUri = $route;
                unset($matches[0]);
                $params = $matches;
                break;
            }
        }
        if (is_callable($this->routes[$requestType][$regexUri])) {
            $this->routes[$requestType][$regexUri](...$params);
        } else {

            if (!empty($regexUri) && $regexUri != "") {
                return $this->callAction(
                    $params,
                    ...explode('@', $this->routes[$requestType][$regexUri])
                );
            }

            if (array_key_exists($uri, $this->routes[$requestType])) {

                return $this->callAction(
                    $params,
                    ...explode('@', $this->routes[$requestType][$uri])
                );
            }

            throw new \Exception("Oops, you forgot to include <b>/{$uri}</b>, There is no such route! ", 404);
            exit;
        }
    }
    protected function callAction($params, $controller, $action) {

        $controller = "Babel\\Controllers\\{$controller}";

        if (!class_exists($controller)) {
            throw new \Exception("Class <b>$controller</b> doesn't not exist!", 500);
        }

        $controller = new $controller;

        $name = get_class($controller);

        if (!method_exists($controller, $action)) {

            throw new \Exception("{$name} doesn't not respond to {$action} Method!", 500);
        }

        return $controller->$action(...$params);
    }
}
