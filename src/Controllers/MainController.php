<?php

namespace Tabel\Controllers;

use Tabel\Core\Middleware;
use Tabel\Core\Request;
use Tabel\Database\Model;
use Tabel\Modules\Paginator;


abstract class MainController {
    protected ?Model $user = null;
    public array $validationErrors = [];

    public function __construct() {
        // one-shot "old input" after a failed form submit
        $GLOBALS['__old'] = $_SESSION['_old'] ?? [];
        unset($_SESSION['_old']);
        $this->validationErrors = Request::getInstance()->validator()->getErrors();
    }
    public function middleware(string $middleware) {
        return (new Middleware)->middleware($middleware);
    }
    public function request() {
        return Request::getInstance();
    }
    /** Check the CSRF token by hand (the Router already does this for every POST when APP_CSRF=true). */
    public function verifyCsrf(): void {
        \Tabel\Modules\Csrf::verify();
    }
    /** Input exactly as sent (trimmed, not HTML-escaped). Escape on output with e(). */
    public function raw(string $key, mixed $default = null): mixed {
        return $this->request()->raw($key, $default);
    }
    public function validate(array $rules): bool {
        return $this->request()->validate($rules);
    }
    public function upload(array $file, string $location, int $max_size, array $mime_types) {
        return $this->request()->upload($file, $location, $max_size, $mime_types);
    }
    public function paginate(array $data, int $per_page) {
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

    /**
     * With APP_CSRF=true the framework router already rejects tokenless POSTs. If someone turns
     * that off, still check here so this app never runs unprotected.
     */
    protected function ensureCsrf(): void {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !\Tabel\Modules\Csrf::enabled()) {
            \Tabel\Modules\Csrf::verify();
        }
    }

    protected function post(string $key, string $default = ''): string {
        $v = $_POST[$key] ?? $default;
        return is_array($v) ? $default : trim((string) $v);
    }

    protected function postArray(string $key): array {
        $v = $_POST[$key] ?? [];
        return is_array($v) ? $v : [];
    }

    protected function query(string $key, string $default = ''): string {
        $v = $_GET[$key] ?? $default;
        return is_array($v) ? $default : trim((string) $v);
    }

    /**
     * Validate $_POST against a field spec: name => ['label'=>, 'rules'=>'required|max:100|numeric|email|date|in:a,b|min:0'].
     * @return array{0: array<string,string>, 1: array<string,string>} [cleaned data, errors]
     */
    protected function check(array $spec): array {
        $data = [];
        $errors = [];
        foreach ($spec as $name => $def) {
            $label = $def['label'] ?? ucfirst(str_replace('_', ' ', $name));
            $value = ($def['type'] ?? '') === 'checkbox' ? (isset($_POST[$name]) ? '1' : '0') : $this->post($name);
            $data[$name] = $value;

            foreach (explode('|', $def['rules'] ?? '') as $rule) {
                if ($rule === '') {
                    continue;
                }
                [$r, $arg] = array_pad(explode(':', $rule, 2), 2, null);
                $empty = $value === '';
                $msg = match (true) {
                    $r === 'required' && $empty => "{$label} is required.",
                    $empty => null,
                    $r === 'max' && mb_strlen($value) > (int) $arg => "{$label} must be at most {$arg} characters.",
                    $r === 'numeric' && !is_numeric($value) => "{$label} must be a number.",
                    $r === 'min' && is_numeric($value) && (float) $value < (float) $arg => "{$label} must be at least {$arg}.",
                    $r === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL) => "{$label} must be a valid email address.",
                    $r === 'date' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) || strtotime($value) === false) => "{$label} must be a valid date.",
                    $r === 'in' && !in_array($value, explode(',', (string) $arg), true) => "{$label} is not a valid choice.",
                    default => null,
                };
                if ($msg) {
                    $errors[$name] = $msg;
                    break;
                }
            }
        }
        return [$data, $errors];
    }
    protected function fail(string $message, string $to): never {
        flash('danger', $message);
        $_SESSION['_old'] = $_POST;
        redirect($to);
    }

    protected function failErrors(array $errors, string $to): never {
        foreach ($errors as $e) {
            flash('danger', $e);
        }
        $_SESSION['_old'] = $_POST;
        redirect($to);
    }

    /** Run a service call; business-rule failures become a flash message + redirect back. */
    protected function attempt(callable $fn, string $failTo): mixed {
        try {
            return $fn();
        } catch (\Exception $e) {
            $this->fail($e->getMessage(), $failTo);
        }
    }

    protected function ok(string $message, string $to): never {
        flash('success', $message);
        redirect($to);
    }

    protected function csv(string $filename, array $headings, array $rows): never {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) . '"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $headings);
        foreach ($rows as $r) {
            // neutralise spreadsheet formula injection
            fputcsv($out, array_map(fn($c) => is_string($c) && preg_match('/^[=+\-@]/', $c) ? "'" . $c : $c, $r));
        }
        fclose($out);
        exit;
    }
}
