<?php

namespace Tabel\Core;

use Tabel\Core\Router;
use Tabel\Core\Request;
use Tabel\Database\Connection;
use Illuminate\Database\Capsule\Manager as Capsule;

class App {
    protected $container = [];

    public $path = '';
    private static $instance;
    protected $booted = false;

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
        return array_get($this->container, $key);
    }

    public function configure(): self {
        $this->bind('app-path', $this->path);
        $this->bind('config', Config::load());
        $this->bind('middlewares', []);
        return $this;
    }

    public function registerMiddleware($middleware) {
        $middlewares = $this->get('middlewares');
        $middlewares[] = $middleware;
        $this->bind('middlewares', $middlewares);
    }

    public function boot(): void {
        if ($this->booted) {
            return;
        }

        $capsule = new Capsule;

        // Register the capsule instance in the container
        $this->bind('db', $capsule);
        $capsule = $this->get('db');
        
        // Set up the database connection
        Connection::make($this->get('config.db'), $capsule);

        $this->booted = true;
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
        return $this;
    }

    public function withExceptions(callable $middleware): self {
        return $this;
    }

    public function create(): self {
        // Boot all service providers
        $this->boot();

        if (php_sapi_name() === 'cli') {
            $this->handleCli();
        } else {
            $this->handleHttp();
        }
        return $this;
    }

    private function handleHttp(): void {
        try {
            Router::load($this->get('web-routes'))->direct(Request::uri(), Request::method());
        } catch (\Exception $e) {
            abort($e->getMessage(), $e->getCode());
        }
    }

    private function handleCli(): void {
        global $argv;

        if (count($argv) < 2) {
            $this->showAvailableCommands();
            exit(1);
        }

        $commandName = $argv[1];
        $commandClass = $this->getCommandClass($commandName);

        if (!$commandClass) {
            echo "Command '{$commandName}' not found.\n";
            $this->showAvailableCommands();
            exit(1);
        }

        $command = new $commandClass($this->get('logger'));
        $exitCode = $command->handle();

        exit($exitCode);
    }

    private function getCommandClass(string $commandName): string {
        $commands = require $this->get('console-routes');

        return $commands[$commandName] ?? '';
    }
    private function showAvailableCommands(): void {
        $commands = require $this->get('console-routes');

        echo "\nAvailable commands:\n";
        foreach (array_keys($commands) as $command) {
            echo "  - {$command}\n";
        }
        echo "\n";
    }
}
