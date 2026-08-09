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
     * @param string|null $cacheFile Optional path to a cache file for storing compiled routes
     * @return self Instance of Router class
     */
    public static function load(string $file, ?string $cacheFile = null) {
        $router = new static;

        if ($cacheFile !== null && file_exists($cacheFile)) {
            static::$routes = require $cacheFile;
            logger("Info", "Router: Loaded routes from cache file: {$cacheFile}");
            return $router;
        }

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
    $matchedRoute = null;

    // Normalize incoming URI (e.g., "/" -> "", "/projects/foo" -> "projects/foo")
    $uri = trim($uri, '/');

    foreach (Router::$routes[$requestType] ?? [] as $route => $controller) {
        if (preg_match("%^{$route}$%", $uri, $matches) === 1) {
            $matchedRoute = $route;
            unset($matches[0]);
            $params = array_values($matches);
            break;
        }
    }

    if ($matchedRoute === null) {
        // no route matched at all - 404
        abort("Route \"{$uri}\" not found", 404);
    }

    $controller = Router::$routes[$requestType][$matchedRoute];

    if (is_callable($controller)) {
        return $controller(...$params);
    }

    if (is_array($controller)) {
        return $this->callAction($params, ...$controller);
    }

    return $this->callAction($params, ...explode('@', $controller));
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

    /**
     * Cache the currently registered routes to a file as a plain PHP array
     *
     * @param ?string $file Path to write the cache file to
     * @throws \Exception if any route uses a non-cacheable Closure controller
     */
    public static function cache(?string $file = null): void {
        if (!$file) {
            $file = app()->get('cache-routes');
        }

        if (!$file) {
            throw new \Exception("No cache file path provided and 'cache-routes' config is not set.");
        }
        foreach (static::$routes as $method => $routes) {
            foreach ($routes as $uri => $controller) {
                if ($controller instanceof \Closure) {
                    throw new \Exception(
                        "Cannot cache route [{$method} {$uri}]: closures aren't cacheable. " .
                            "Use 'Controller@method' or [Controller::class, 'method'] instead."
                    );
                }
            }
        }

        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $export = var_export(static::$routes, true);
        $content = "<?php\n\n// Auto-generated route cache - do not edit directly.\n" .
            "// Generated: " . date('Y-m-d H:i:s') . "\n\nreturn {$export};\n";

        file_put_contents($file, $content);
    }

    /**
     * Delete the route cache file if it exists
     *
     * @param string $file Path to the cache file
     * @return bool True if a cache file was deleted
     */
    public static function clearCache(?string $file = null): bool {
        if (!$file) {
            $file = app()->get('cache-routes');
        }

        if (file_exists($file)) {
            return unlink($file);
        }
        return false;
    }
}
