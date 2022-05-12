<?php

use Babel\Core\Mantle\Session;

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
/**
 * singularize
 * This returns the singular version of common english words
 * --from https://www.kavoir.com/2011/04/php-class-converting-plural-to-singular-or-vice-versa-in-english.html
 * 
 * @param string $phrase the word to be pluralised
 * @param int $value 
 * 
 * @return string plural 
 */

function singularize($word) {
    $singular = array(
        '/(quiz)zes$/i' => '\1',
        '/(matr)ices$/i' => '\1ix',
        '/(vert|ind)ices$/i' => '\1ex',
        '/^(ox)en/i' => '\1',
        '/(alias|status)es$/i' => '\1',
        '/([octop|vir])i$/i' => '\1us',
        '/(cris|ax|test)es$/i' => '\1is',
        '/(shoe)s$/i' => '\1',
        '/(o)es$/i' => '\1',
        '/(bus)es$/i' => '\1',
        '/([m|l])ice$/i' => '\1ouse',
        '/(x|ch|ss|sh)es$/i' => '\1',
        '/(m)ovies$/i' => '\1ovie',
        '/(s)eries$/i' => '\1eries',
        '/([^aeiouy]|qu)ies$/i' => '\1y',
        '/([lr])ves$/i' => '\1f',
        '/(tive)s$/i' => '\1',
        '/(hive)s$/i' => '\1',
        '/([^f])ves$/i' => '\1fe',
        '/(^analy)ses$/i' => '\1sis',
        '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '\1\2sis',
        '/([ti])a$/i' => '\1um',
        '/(n)ews$/i' => '\1ews',
        '/s$/i' => '',
    );

    $uncountable = array('equipment', 'information', 'rice', 'money', 'species', 'series', 'fish', 'sheep');

    $irregular = array(
        'person' => 'people',
        'man' => 'men',
        'child' => 'children',
        'sex' => 'sexes',
        'move' => 'moves'
    );

    $lowercased_word = strtolower($word);
    foreach ($uncountable as $_uncountable) {
        if (substr($lowercased_word, (-1 * strlen($_uncountable))) == $_uncountable) {
            return $word;
        }
    }

    foreach ($irregular as $_plural => $_singular) {
        if (preg_match('/(' . $_singular . ')$/i', $word, $arr)) {
            return preg_replace('/(' . $_singular . ')$/i', substr($arr[0], 0, 1) . substr($_plural, 1), $word);
        }
    }

    foreach ($singular as $rule => $replacement) {
        if (preg_match($rule, $word)) {
            return preg_replace($rule, $replacement, $word);
        }
    }

    return $word;
}

function build_table($array) {
    // start table
    $html = "<table class=\"w-full text-sm text-left text-gray-500 dark:text-gray-400\">";
    // header row
    $html .= "<thead class=\"sticky top-0  text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400\">";
    $html .= '<tr>';
    foreach ($array[0] as $key => $value) {
        $html .= '<th  scope="col" class="sticky top-0  px-6 py-3" >' . htmlspecialchars($key) . '</th>';
    }
    $html .= '</tr>';
    $html .= "</thead>";
    // data rows
    $html .= ' <tbody class="overflow-y-auto">';
    foreach ($array as $key => $value) {
        $html .= '<tr class="border-b dark:bg-gray-800 dark:border-gray-700 odd:bg-white even:bg-gray-50 odd:dark:bg-gray-800 even:dark:bg-gray-700">';
        foreach ($value as $key2 => $value2) {
            $html .= '<td class="px-6 py-4">' . htmlspecialchars($value2) . '</td>';
        }
        $html .= '</tr>';
    }
    $html .= ' </tbody>';
    // finish table and return it

    $html .= '</table>';

    return $html;
}


function startsWith($startString, $string) {
    $len = strlen($startString);
    return (substr($string, 0, $len) === $startString);
}
