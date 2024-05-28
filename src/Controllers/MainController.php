<?php

namespace Tabel\Controllers;

use Tabel\Core\Middleware;
use Tabel\Core\Modules\Request;
use Tabel\Core\Modules\Paginator;


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
     * Return a json response
     * 
     * @param mixed $values what to send back
     * @param int $code http res code
     * 
     * @return void
     */
    public function json(mixed $values, int $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        //header('Access-Control-Allow-Origin : *;');
        echo json_encode($values, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
