<?php

namespace Tabel\Core;

use Tabel\Modules\Upload;
use Tabel\Modules\Validator;

class Request {
    public static $instance = null;
    protected $data = [];
    protected $errors = [];
    protected $headers = [];

    /**
     * Initialize request data and headers
     */
    public function __construct() {
        $this->data = array_merge($_POST, $_GET, $_FILES);
        $this->headers = $this->getRequestHeaders();
    }

    /**
     * Get singleton instance of Request
     * 
     * @return self
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get all validation errors
     * 
     * @return array
     */
    public function getErrors(): array {
        return $this->errors;
    }

    /**
     * Set a validation error
     * 
     * @param string $key
     * @param mixed $value
     */
    public function setError(string $key, mixed $value): void {
        $this->errors[$key] = $value;
    }

    /**
     * Get all request data
     * 
     * @return array
     */
    public function all(): array {
        return $this->data;
    }

    /**
     * Get a specific input value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function input(string $key, mixed $default = null): mixed {
        if (!isset($this->data[$key])) {
            return $default;
        }

        if (is_array($this->data[$key])) {
            return array_map([$this, 'sanitize'], $this->data[$key]);
        }

        return $this->sanitize($this->data[$key]) ?? $default;
    }

    /**
     * Check if input exists
     * 
     * @param string|array $keys
     * @return bool
     */
    public function has(string|array $keys): bool {
        $keys = is_array($keys) ? $keys : [$keys];
        foreach ($keys as $key) {
            if (!isset($this->data[$key])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get only specified inputs
     * 
     * @param array $keys
     * @return array
     */
    public function only(array $keys): array {
        return array_intersect_key($this->data, array_flip($keys));
    }

    /**
     * Get all inputs except specified ones
     * 
     * @param array $keys
     * @return array
     */
    public function except(array $keys): array {
        return array_diff_key($this->data, array_flip($keys));
    }

    /**
     * Sanitize input value
     * 
     * @param mixed $input
     * @return string|null
     */
    private function sanitize(mixed $input): ?string {
        if ($input === null) {
            return null;
        }
        return htmlspecialchars(trim((string)$input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Get a value from GET parameters
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed {
        return isset($_GET[$key]) ? $this->sanitize($_GET[$key]) : $default;
    }

    /**
     * Get a value from POST parameters
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function post(string $key, mixed $default = null): mixed {
        return isset($_POST[$key]) ? $this->sanitize($_POST[$key]) : $default;
    }

    /**
     * Get the current URI
     * 
     * @return string
     */
    public static function uri(): string {
        return trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    }

    /**
     * Get the request method
     * 
     * @return string
     */
    public static function method(): string {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }

    /**
     * Check if request is AJAX
     * 
     * @return bool
     */
    public function isAjax(): bool {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Get request headers
     * 
     * @return array
     */
    protected function getRequestHeaders(): array {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }

        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) === 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }

    /**
     * Get a request header
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function header(string $key, mixed $default = null): mixed {
        return $this->headers[$key] ?? $default;
    }

    /**
     * Validate request data
     * 
     * @param array $rules
     * @return bool
     */
    public function validate(array $rules): bool {
        return $this->validator()->validate($this, $rules);
    }

    /**
     * Get validator instance
     * 
     * @return Validator
     */
    public function validator(): Validator {
        return Validator::getInstance();
    }

    /**
     * Handle file upload
     * 
     * @param array $file
     * @param string $location
     * @param int $max_size
     * @param array $mime_types
     * @return string|null
     * @throws \Exception
     */
    public function upload(array $file, string $location, int $max_size, array $mime_types): ?string {
        if (empty($file)) {
            throw new \Exception("No file provided for upload", 400);
        }

        try {
            $upload = Upload::factory($location);
            $upload->file($file);
            $upload->set_max_file_size($max_size);
            $upload->set_allowed_mime_types($mime_types);

            $results = $upload->upload();

            if (empty($results['path'])) {
                $this->setError('upload', $results);
                return null;
            }

            return $results['path'];
        } catch (\Exception $e) {
            $this->setError('upload', $e->getMessage());
            throw new \Exception("File upload failed: " . $e->getMessage(), 500);
        }
    }

    /**
     * Handle AJAX form submission
     * 
     * @return array
     */
    public function handleAjaxForm(): array {
        if (!$this->isAjax()) {
            return [
                "status" => "fail",
                "message" => "Not an AJAX request"
            ];
        }

        if (empty($_POST)) {
            return [
                "status" => "fail",
                "message" => "Please fill in the form"
            ];
        }

        return [
            "status" => "success",
            "message" => "Form submitted successfully"
        ];
    }

    /**
     * Get client IP address
     * 
     * @return string
     */
    public function ip(): string {
        return $_SERVER['HTTP_CLIENT_IP'] 
            ?? $_SERVER['HTTP_X_FORWARDED_FOR'] 
            ?? $_SERVER['REMOTE_ADDR'] 
            ?? '';
    }

    /**
     * Get user agent
     * 
     * @return string
     */
    public function userAgent(): string {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Check if request is secure (HTTPS)
     * 
     * @return bool
     */
    public function isSecure(): bool {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || $_SERVER['SERVER_PORT'] == 443;
    }
}
