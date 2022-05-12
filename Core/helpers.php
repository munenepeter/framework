<?php

use App\Core\Mantle\Session;

/**
 * checkCreateView
 * 
 * Create a view for route if it does not exist
 * 
 * @param String $view view to be created
 * 
 * @return Void
 */
function checkCreateView(string $view) {
    if (!file_exists("views/{$view}.view.php")) {

        if (ENV === 'production') {
            throw new \Exception("The file is missing", 404);
            exit;
        }
        fopen("views/{$view}.view.php", 'a');

        $data = "<?php include_once 'base.view.php';?><div class=\"grid place-items-center h-screen\">
       Created {$view}'s view; please edit</div>";

        file_put_contents("views/{$view}.view.php", $data);
    }
}

/**
 * View
 * 
 * Loads a specified file along with its data
 * 
 * @param String $filename Page to displayed
 * @param Array $data Data to be passed along
 * 
 * @return Require view
 */
function view(string $filename, array $data = []) {
    extract($data);

    checkCreateView($filename);

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

function redirectback($data) {
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
