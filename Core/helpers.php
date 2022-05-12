<?php

use App\Core\Mantle\Session;

/**
 * View
 * 
 * Loads a specified file along with its data
 * 
 * @param String $filename Page to displayed
 * @param Array $data Data to be passed along
 * 
 * @return File view
 */
function view($filename, $data = []) {
    extract($data);
    return require "views/{$filename}.view.php";
}
/**
 * Redirect
 * 
 * Redirects to a give page
 * 
 * @param String $path Page to be redirected to
 */
function redirect(string $path) {
    header("location:{$path}");
}
/**
 * Abort
 * 
 * Kills the execution of the script & diplays error page
 * 
 * @param String $message The exception/erro msg
 * @param Int $code Status code passed with the exception
 * 
 * @return File view
 */
function abort($message, $code) {

    //   http_response_code($code);

    view('error', [
        'code' => $code,
        'message' => $message
    ]);
    exit;
}

function redirectback($data){
    extract($data);
    redirect($_SERVER['HTTP_REFERER']);
}
/**
 * Auth Helper
 * 
 * Returns true of false incase one is loggedin
 * 
 * @return Bool Session
 */
function auth() {

    if (Session::get('loggedIn') === NULL || Session::get('loggedIn') === false) {
        return false;
    }
    return Session::get('loggedIn');
}
/**
 * Guest Helper
 * 
 * Opposite of Auth
 * 
 * @return Bool Session
 */
function guest() {
    if (auth()) {
        return false;
    }
}

/**
 * plural
 * This returns the plural version of common english words
 * --from stackoverflow
 * 
 * @param string $phrase the word to be pluralised
 * @param int $value 
 * 
 * @return string plural 
 */

function plural($phrase, $value) {
    $plural = '';
    if ($value > 1) {
        for ($i = 0; $i < strlen($phrase); $i++) {
            if ($i == strlen($phrase) - 1) {
                $plural .= ($phrase[$i] == 'y') ? 'ies' : (($phrase[$i] == 's' || $phrase[$i] == 'x' || $phrase[$i] == 'z' || $phrase[$i] == 'ch' || $phrase[$i] == 'sh') ? $phrase[$i] . 'es' : $phrase[$i] . 's');
            } else {
                $plural .= $phrase[$i];
            }
        }
        return $plural;
    }
    return $phrase;
}







/*
    function auth() {
        $a = true;

        $class = new class {
            public $name = "Peter" ;

            public function name() {
              echo $this->name;
            }
        };

        if ($a) {
            return $class;
        }
        return $a;
    }

    auth()->name();
*/
