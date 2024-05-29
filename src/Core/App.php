<?php

namespace Tabel\Core;

use Tabel\Core\Router;
use Tabel\Core\Request;
use Tabel\Core\Mantle\Middleware;

class App {
    protected $container = [];

    public $path = '';
    private static $instance;

    private function __construct(string $basePath) {
        $this->path = $basePath;
    }

    public static function getInstance(string $basePath): self {
        if (self::$instance === null) {
            self::$instance = new self($basePath);
        }
        return self::$instance;
    }
    public function bind($key, $value) {
        $this->container[$key] = $value;
    }

    public function get($key) {
        // if (!array_key_exists($key, $this->container)) {
        //     throw new \Exception("The key '{$key}' was not found in the container", 500);
        // }
        return array_get($this->container, $key);
    }

    public function configure(): self {

        $this->bind('app-path', $this->path);

        $this->bind('config', Config::load());

        $this->bind('middlewares', []); // Initialize empty middleware array
        //App::bind('mailer', new Mail(App::get('config.mail')));
        // App::bind('config', Config::load()); 

        // ... add other configuration logic (e.g., database, sessions)

        return $this;
    }


    public function registerMiddleware( $middleware) {
        $middlewares = $this->get('middlewares');
        $middlewares[] = $middleware;
        $this->bind('middlewares', $middlewares);
    }
    public function withRouting(array $routes): self {
        foreach ($routes as $layer => $route_file) {
            if ($layer === 'web') {
                $this->bind('web-routes', $route_file);
            }
            if ($layer === 'console') {
                $this->bind('console-routes', $route_file);
            }
        }
        return $this;
    }
    public function withMiddleware(callable $middleware): self {
         # TODO
        return $this;
    }
    public function withExceptions(callable $middleware): self {
        # TODO
        return $this;
    }

    public  function create(): self {
        try {
            Router::load($this->get('web-routes'))->direct(Request::uri(), Request::method());
        } catch (\Exception $e) {
            //Instead of catching the exception here we redirect the same to our main error handler
            abort($e->getMessage(), $e->getCode());
        }


        return $this;
    }
}
