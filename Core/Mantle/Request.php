<?php

namespace Tabel\Core\Mantle;

use Tabel\Core\Mantle\Upload;
use Tabel\Core\Mantle\Validator;


class Request {

    public static $instance = null;

    protected $data = [];
    protected $errors = [];

    public function __construct() {
        $this->data = array_merge($_POST, $_GET, $_FILES);
    }
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getErrors() {
        return $this->errors;
    }
    public function setError($key, $value) {
        $this->errors[$key] = $value;
    }

    public function all() {
        return $this->data;
    }

    public function input($key, $default = null) {

        if (is_array($this->data[$key]))
            return $this->data[$key];

        return $this->sanitize($this->data[$key]) ?? $default;
    }

    private function sanitize(string $input) {
        return htmlspecialchars(trim($input));
    }


    public function get(string $key) {
        if (!isset($_GET[$key])) {
            return false;
        }
        return htmlspecialchars(trim($_GET[$key]));
    }


    //get the current URI
    public static function uri() {
        return trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    }
    //get the method as requested by the router
    public static function method() {
        return $_SERVER['REQUEST_METHOD'];
    }

    public function validate(array $rules) {
        return $this->validator()->validate($this, $rules);
    }
    public function validator(){
       return Validator::getInstance();
    }
    public function upload(array $file, string $location, int $max_size, array $mime_types) {
        // upload the file
        if (!empty($file)) {

            $upload = Upload::factory($location);

            $upload->file($file);

            //set max. file size (in mb)
            $upload->set_max_file_size($max_size);

            //set allowed mime types
            $upload->set_allowed_mime_types($mime_types);


            $results = $upload->upload();

            if (empty($results['path'])) {
                $this->setError('upload', $results);
                return;
            }

            return $results['path'];
        }
        throw new \Exception("Nothing was uploaded", 500);
    }

    public function handleAjaxForm() {
        if (!isset($_POST) || empty($_POST)) {
            $data["status"] = "fail";
            $data["message"] = "Please fill in the form";
            return $data;
        } else {
            $data["status"] = "success";
            $data["message"] = "Updated User";
            return $data;
        }
    }
}
