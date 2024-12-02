<?php

namespace Tabel\Controllers;

use Tabel\Core\Request;
use Tabel\Core\Middleware;
use Tabel\Modules\Paginator;


abstract class MainController {
    public $validationErrors = [];

    public function __construct() {
        $this->validationErrors = Request::getInstance()->validator()->getErrors();
    }


    public function middleware($middleware) {
        return (new Middleware)->middleware($middleware);
    }
    public function request() {
        return Request::getInstance();
    }
    public function validate(array $rules): bool {
        return $this->request()->validate($rules);
    }
    public function upload(array $file, string $location, int $max_size, array $mime_types) {
        return $this->request()->upload($file, $location, $max_size, $mime_types);
    }
    public function paginate(array $data, $per_page) {
        return Paginator::paginate($data, $per_page);
    }
    /**
     * Return a json response with proper headers
     * 
     * @param mixed $values Data to send back
     * @param int $code HTTP response code
     * @param array $headers Additional headers
     * 
     * @return void
     */
    public function json(mixed $values, int $code = 200, array $headers = []) {
        if (headers_sent()) {
            throw new \Exception("Cannot send JSON response, headers already sent");
        }
        
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        
        foreach ($headers as $key => $value) {
            header("$key: $value");
        }
        
        echo json_encode($values, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }

    /**
     * Return a success response
     * 
     * @param mixed $data Data to return
     * @param string $message Success message
     * @param int $code HTTP status code
     * 
     * @return void
     */
    public function success(mixed $data = null, string $message = 'Success', int $code = 200) {
        $response = [
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ];
        
        $this->json($response, $code);
    }

    /**
     * Return an error response
     * 
     * @param string $message Error message
     * @param int $code HTTP status code
     * @param mixed $errors Additional error details
     * 
     * @return void
     */
    public function error(string $message = 'Error', int $code = 400, mixed $errors = null) {
        $response = [
            'status' => 'error',
            'message' => $message,
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        $this->json($response, $code);
    }

    /**
     * Validate request and return error response if validation fails
     * 
     * @param array $rules Validation rules
     * @param string $errorMessage Custom error message
     * 
     * @return bool
     */
    public function validateOrFail(array $rules, string $errorMessage = 'Validation failed'): bool {
        if (!$this->validate($rules)) {
            $this->error($errorMessage, 422, $this->validationErrors);
            return false;
        }
        return true;
    }

    /**
     * Get a specific input from the request
     * 
     * @param string $key Input key
     * @param mixed $default Default value if key doesn't exist
     * 
     * @return mixed
     */
    public function input(string $key, mixed $default = null): mixed {
        return $this->request()->input($key, $default);
    }

    /**
     * Get all inputs from the request
     * 
     * @param array $except Keys to exclude
     * 
     * @return array
     */
    public function all(array $except = []): array {
        $inputs = $this->request()->all();
        return empty($except) ? $inputs : array_diff_key($inputs, array_flip($except));
    }

    /**
     * Safely handle file uploads with validation
     * 
     * @param string $field Form field name
     * @param string $location Upload location
     * @param array $options Upload options (max_size, mime_types)
     * 
     * @return string|false Returns filename on success, false on failure
     */
    public function handleUpload(string $field, string $location, array $options = []) {
        $file = $_FILES[$field] ?? null;
        if (!$file) {
            $this->error("No file uploaded", 400);
            return false;
        }

        $options = array_merge([
            'max_size' => 5 * 1024 * 1024, // 5MB default
            'mime_types' => ['image/jpeg', 'image/png', 'image/gif']
        ], $options);

        try {
            return $this->upload($file, $location, $options['max_size'], $options['mime_types']);
        } catch (\Exception $e) {
            $this->error($e->getMessage(), 400);
            return false;
        }
    }
}
