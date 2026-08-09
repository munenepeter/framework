<?php

use Tabel\Core\App;
use Tabel\Core\Auth;
use Tabel\Core\Request;
use Tabel\Modules\Logger;
use Tabel\Modules\Session;


if (!defined('BASE_URL')) {
    define("BASE_URL", Request::isCli() ? '' : sprintf(
        "%s://%s",
        isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
        $_SERVER['SERVER_NAME'] ?? 'localhost'
    ));
}


/**
 * checkCreateView
 * 
 * Create a view for route if it does not exist
 * 
 * @param string $view view to be created
 * 
 * @return void
 */
function checkView(string $filename) {
    if (!file_exists($filename)) {
        logger("Error", "The view for {$filename} appears to be missing");
        throw new \Exception("The requested view file '{$filename}' cannot be found", 404);
    }
}

/**
 * View
 * 
 * Loads a specified file along with its data
 * 
 * @param string $filename Page to displayed
 * @param array $data Data to be passed along
 * 
 * @return bool view
 */
function view(string $filename, array $data = []) {
    extract($data);
    $filename = app()->get('app-path') . "/views/{$filename}.view.php";

    checkView($filename);

    return require_once $filename;
}

/**
 * Redirect
 * 
 * Redirects to a give page
 * 
 * @param string $path Page to be redirected to
 */
function redirect(string $path) {
    if (!headers_sent()) {
        header("Location: {$path}");
        exit;
    } else {
        throw new \Exception("Cannot redirect, headers already sent");
    }
}
/**
 * Abort
 * 
 * Kills the execution of the script & diplays error page
 * 
 * @param string $message The exception/error msg
 * @param int $code Status code passed with the exception
 * @param string|null $file File the error originated in (optional, used in dev view)
 * @param int|null $line Line the error originated on (optional, used in dev view)
 * @param array|null $trace debug_backtrace()-style trace (optional, used in dev view)
 * 
 * @return string view
 */
function abort($message, $code, ?string $file = null, ?int $line = null, ?array $trace = null) {
    $code = filter_var($code, FILTER_VALIDATE_INT);
    
    if ($code === 0 || is_string($code) || $code === "") {
        $code = 500;
    }
    http_response_code($code);

    if (Request::expectsJson()) {
        header('Content-Type: application/json; charset=utf-8');
        $response = [
            'success' => false,
            'code' => $code,
            'message' => $message,
        ];

        if (is_dev()) {
            $response['file'] = $file;
            $response['line'] = $line;
            $response['trace'] = $trace;
        }

        echo json_encode(
            $response,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        exit;
    }
    view_internal('_error', [
        'code' => $code,
        'message' => $message,
        'file' => $file,
        'line' => $line,
        'trace' => $trace,
    ]);
    exit;
}
function view_internal(string $filename, array $data = []) {
    extract($data);
    $filename = __DIR__ . "/src/Views/{$filename}.view.php";

    checkView($filename);

    return require_once $filename;
}
function app(): App {
    return App::getInstance(getcwd());
}


function redirectback($data = []) {
    extract($data);
    if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] !== '') {
        redirect($_SERVER['HTTP_REFERER']);
    }
    $back = (new Request)->get('back');
    if (!$back) {
        redirect('/');
    }
    redirect($back);
}

function request_uri() {
    return Request::uri();
}

function wp_strip_all_tags($string, $remove_breaks = false) {
    $string = preg_replace('@<(script|style)[^>]*?>.*?</\\1>@si', '', $string);
    $string = strip_tags($string);

    if ($remove_breaks) {
        $string = preg_replace('/[\r\n\t ]+/', ' ', $string);
    }

    return trim($string);
}

function getRandColor() {
    $rgbColor = [];
    foreach (['r', 'g', 'b'] as $color) {
        $rgbColor[$color] = mt_rand(0, 255);
    }
    $colorCode = implode(",", $rgbColor);
    return "rgb($colorCode)";
}
/**
 * subtract_date
 * 
 * Subtracts a number of days from a date
 * 
 * @param int $days_to_subtract no of days to subtract
 * 
 * @return string the date after subtracting
 */

function subtract_date(int $days_to_subtract) {
    $date = date_create(date('Y-m-d H:i:s', time()));
    date_sub($date, date_interval_create_from_date_string("$days_to_subtract days"));
    return date_format($date, 'Y-m-d H:i:s');
}

function slug(string $string) {
    return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
}

/**
 * Auth Helper
 * 
 * Returns the status of login & an object helper
 * 
 * @return bool|object Session
 */
function auth() {

    if (Session::get('loggedIn') === NULL || Session::get('loggedIn') === false) {
        return false;
    }

    $class = new class {

        public $username;
        public $email;
        public $id;

        public function __construct() {
            $user = Auth::user();
            $this->username = $user->username ?? null;
            $this->email = $user->email ?? null;
            $this->id =  $user->id ?? null;
        }
        public function __get($name) {
            return $name;
        }
        public function __set($name, $value) {
            $this->$name = $value;
        }
        public function logout() {
            Auth::logout(Auth::user());
            redirect('/');
        }
    };

    return $class;
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

/**
 * Delete a file
 */
function delete_file(string $path) {
    if (!unlink($path)) {
        logger("Error", "File cannot be deleted due to an error");
        return false;
    } else {
        logger("Info", "A File has been deleted");
        return true;
    }
}

function downloadFile($dir, $file) {
    $filePath = $dir . $file;
    if (file_exists($filePath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        flush(); // Flush system output buffer
        readfile($filePath);
        exit;
    } else {
        http_response_code(404);
        throw new \Exception("File '{$file}' not found for download");
    }
}


/**
 * dd
 * 
 * dump the results & die
 * 
 * @param mixed $data view to be created
 * 
 * @return string
 */

function dd($var) {
    //to do
    // debug_print_backtrace();
    if (php_sapi_name() === 'cli') {
        var_dump($var);
        die();
    }

    ini_set("highlight.keyword", "#a50000;  font-weight: bolder");
    ini_set("highlight.string", "#5825b6; font-weight: lighter; ");

    ob_start();
    highlight_string("<?php\n" . var_export($var, true) . "?>");
    $highlighted_output = ob_get_clean();

    $highlighted_output = str_replace(["&lt;?php", "?&gt;"], '', $highlighted_output);

    echo $highlighted_output;
    die();
}
/**
 * url helper
 * 
 * @return string url in relation to where it is called
 * 
 * from https://stackoverflow.com/questions/2820723/how-do-i-get-the-base-url-with-php
 */
function url() {
    if (Request::isCli()) {
        return '';
    }

    if (!is_dev()) {
        return sprintf(
            "%s://%s%s",
            isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
            $_SERVER['SERVER_NAME'] ?? 'localhost',
            $_SERVER['REQUEST_URI'] ?? '/'
        );
    } else {
        return sprintf(
            "%s://%s:%s%s",
            isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
            $_SERVER['SERVER_NAME'] ?? 'localhost',
            $_SERVER['SERVER_PORT'] ?? '80',
            $_SERVER['REQUEST_URI'] ?? '/'
        );
    }
}

function format_date($date) {
    return date("jS M Y ", strtotime($date));
}

function time_ago($datetime, $full = false) {
    $now = new \DateTime;
    $ago = new \DateTime($datetime);
    $diff = $now->diff($ago);

    $weeks = floor($diff->d / 7);
    $diff->d -= $weeks * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
/**
 * asset helper
 * 
 * @param $dir director to be returned in respect to the static dir
 * 
 * @return void  echo Path to the requested resource
 */
function asset(string $path): void {
    $path = ltrim($path, '/');

    if (Request::isCli()) {
        echo "/$path";
        return;
    }

    $port = $_SERVER['SERVER_PORT'] ?? '';

    if (is_dev() && !in_array($port, ['80', '443'])) {
        echo BASE_URL . ":{$port}/{$path}";
    } else {
        echo BASE_URL . "/{$path}";
    }
}
function get_perc($total, $number) {
    if ($total > 0) {
        return round(($number * 100) / $total, 2);
    } else {
        return 0;
    }
}

function logger(string $level, string $message) {
    Logger::log($level, $message);
}

function request(string $key, $default = null) {
    if (Request::isCli()) {
        global $argv;
        // Parse CLI arguments if needed
        return $default;
    }

    if (isset($_REQUEST[$key])) {
        return htmlspecialchars(trim($_REQUEST[$key]));
    }
    return $default;
}
function session_get($value) {
    return Session::get($value);
}
function is_dev() {
    if (app()->get('config.app.env') === 'development') {
        return true;
    } elseif (app()->get('config.app.env') === 'production') {
        return false;
    }
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
function truncate(string $text, int $limit) {
    return mb_strlen($text, 'UTF-8') > $limit ? mb_substr($text, 0, $limit, 'UTF-8') . "…" : $text;
}


function build_table($array) {
    // start table
    $html = "<table class=\"w-full text-sm text-left text-gray-500 dark:text-gray-400\">";
    // header row
    $html .= "<thead class=\"sticky top-0  text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400\">";
    $html .= '<tr>';
    foreach ($array[0] as $key => $value) {
        $html .= '<th  scope="col" class="sticky top-0 px-6 py-3" >' . htmlspecialchars($key) . '</th>';
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


if (!function_exists('collapse')) {
    function collapse($array) {
        $results = [];

        foreach ($array as $values) {
            if (!is_array($values)) {
                continue;
            }

            $results[] = $values;
        }

        return array_merge([], ...$results);
    }
}

if (!function_exists('value')) {
    /**
     * Return the value, resolving it if it's a Closure.
     *
     * @param mixed $value
     * @param mixed ...$args passed through if $value is callable
     * @return mixed
     */
    function value($value, ...$args) {
        return $value instanceof \Closure ? $value(...$args) : $value;
    }
}

if (!function_exists('array_get')) {
    /**
     * Get an item from an array or object using "dot" notation.
     *
     * @param mixed $target
     * @param string|array|null $key
     * @param mixed $default
     * @return mixed
     */
    function array_get($target, $key, $default = null) {
        if (is_null($key)) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', $key);

        foreach ($key as $i => $segment) {
            unset($key[$i]);

            if (is_null($segment)) {
                return $target;
            }

            if ($segment === '*') {
                if (!is_iterable($target)) {
                    return value($default);
                }

                $result = [];

                foreach ($target as $item) {
                    $result[] = array_get($item, $key);
                }

                return in_array('*', $key) ? collapse($result) : $result;
            }

            $segment = match ($segment) {
                '\*' => '*',
                '\{first}' => '{first}',
                '{first}' => array_key_first(is_array($target) ? $target : $segment),
                '\{last}' => '{last}',
                '{last}' => array_key_last(is_array($target) ? $target : $segment),
                default => $segment,
            };
            if (is_array($target)  && array_key_exists($segment, $target)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return value($default);
            }
        }

        return $target;
    }
}

if (!function_exists('data_get')) {
    /**
     * Alias of array_get() using the conventional Laravel-style name, in
     * case other code (or copy-pasted snippets) expects data_get() directly.
     *
     * @param mixed $target
     * @param string|array|null $key
     * @param mixed $default
     * @return mixed
     */
    function data_get($target, $key, $default = null) {
        return array_get($target, $key, $default);
    }
}


/**
 * Parse stack trace into structured frames with code preview
 * 
 * @param string $trace
 * @return array
 */
function parseStackTrace($trace) {
    $frames = [];
    $lines = explode("\n", $trace);

    foreach ($lines as $line) {
        if (preg_match('/#(\d+)\s+(?:([^(]+)\((.*?)\):\s+)?(?:(.+)\((\d+)\)|(.+))/', $line, $matches)) {
            $frame = [
                'number' => $matches[1] ?? '',
                'function' => $matches[2] ?? '',
                'args' => $matches[3] ?? '',
                'file' => $matches[4] ?? $matches[6] ?? '',
                'line' => $matches[5] ?? '',
                'type' => 'vendor',
            ];

            // Determine if it's application or vendor code
            if (isset($frame['file'])) {
                if (strpos($frame['file'], '/vendor/') === false) {
                    $frame['type'] = 'app';
                }

                // Extract class if available
                if (preg_match('/(\w+)::(\w+)/', $frame['function'], $classMatch)) {
                    $frame['class'] = $classMatch[1];
                    $frame['function'] = $classMatch[2];
                }

                // Get code preview
                $frame['code_preview'] = getCodePreview($frame['file'], $frame['line']);
            }

            $frames[] = $frame;
        }
    }

    return $frames;
}

/**
 * Get code preview around the error line
 * 
 * @param string $file
 * @param int $line
 * @param int $context
 * @return array
 */
function getCodePreview($file, $line, $context = 10) {
    if (!file_exists($file) || !is_readable($file)) {
        return [];
    }

    $lines = file($file);
    $start = max(0, $line - $context - 1);
    $end = min(count($lines), $line + $context);
    $preview = [];

    for ($i = $start; $i < $end; $i++) {
        $preview[$i + 1] = rtrim($lines[$i], "\r\n");
    }

    return $preview;
}

/**
 * Simple syntax highlighting for code
 *
 * Tokenizes the RAW (unescaped) source first using placeholder markers,
 * then HTML-escapes the whole string exactly once, then swaps the
 * placeholders for real <span> tags. Escaping before tokenizing would
 * turn `'` into `&#039;`, whose literal `#` then gets misread as the
 * start of a `#`-style comment by the comment regex, corrupting
 * everything after it on the line.
 *
 * @param string $code
 * @return string
 */
function highlightCode(string $code) {
    $placeholders = [];
    // Token markers use control-byte delimiters wrapping a base-26 letter
    // index (never digits). This keeps them invisible to every later regex
    // pass (numbers \b\d+\b, keywords \b(word)\b, variables $word) no matter
    // how many tokens exist, unlike a raw numeric index which a later
    // "numbers" pass would happily match and corrupt.
    $token = function (string $html) use (&$placeholders) {
        $n = count($placeholders);
        $letters = '';
        do {
            $letters = chr(97 + ($n % 26)) . $letters;
            $n = intdiv($n, 26) - 1;
        } while ($n >= 0);
        $key = "\x01{$letters}\x02";
        $placeholders[$key] = $html;
        return $key;
    };

    // Strings first, so a `#` or `//` inside a string literal isn't
    // mistaken for the start of a comment by the rule below.
    $code = preg_replace_callback(
        '/(["\'])(.*?)\1/',
        fn($m) => $token('<span class="code-string">' . htmlspecialchars($m[0]) . '</span>'),
        $code
    );

    // Comments (// or #) - runs on whatever's left, so string contents
    // (already replaced with placeholders above) are immune.
    $code = preg_replace_callback(
        '/(\/\/.*|#.*)/',
        fn($m) => $token('<span class="code-comment">' . htmlspecialchars($m[1]) . '</span>'),
        $code
    );

    $keywords = [
        'function',
        'class',
        'public',
        'private',
        'protected',
        'static',
        'return',
        'if',
        'else',
        'foreach',
        'for',
        'while',
        'try',
        'catch',
        'throw',
        'new',
        'use',
        'namespace',
        'extends',
        'implements',
        'abstract',
        'final',
        'interface',
        'trait'
    ];
    $code = preg_replace_callback(
        '/\b(' . implode('|', $keywords) . ')\b/',
        fn($m) => $token('<span class="code-keyword">' . $m[1] . '</span>'),
        $code
    );

    // Variables
    $code = preg_replace_callback(
        '/(\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/',
        fn($m) => $token('<span class="code-variable">' . $m[1] . '</span>'),
        $code
    );

    // Numbers
    $code = preg_replace_callback(
        '/\b(\d+)\b/',
        fn($m) => $token('<span class="code-number">' . $m[1] . '</span>'),
        $code
    );

    // Escape whatever plain code text is left, exactly once.
    $code = htmlspecialchars($code, ENT_QUOTES, 'UTF-8', false);

    // Restore placeholders. A single strtr() pass isn't enough when one
    // token's stored HTML contains another token's marker nested inside it
    // (e.g. a string literal placeholder sitting inside a comment's matched
    // text, since comments run after strings and can swallow their marker).
    // Iterate until stable; bounded by placeholder count so it can't loop.
    for ($i = 0, $max = count($placeholders) + 1; $i < $max; $i++) {
        $next = strtr($code, $placeholders);
        if ($next === $code) {
            break;
        }
        $code = $next;
    }

    return $code;
}

function tabelErrorHandler(int|string $severity, string $message, string $file, int|string $line, array $context = []) {
    // Don't handle errors that are suppressed with @
    if (!(error_reporting() & $severity)) {
        return false;
    }

    // Get error type name
    $errorTypes = [
        E_ERROR => 'Fatal Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parse Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'User Deprecated'
    ];

    $errorType = $errorTypes[$severity] ?? 'Unknown Error';

    logger('error', "{$errorType}: {$message} in {$file}:{$line}");

    // No trace passed here on purpose: debug_backtrace() at this point only
    // captures frames inside the error handler itself (tabelErrorHandler ->
    // the registered closure), never the real call site of the error. The
    // file/line PHP already gives us, plus the code snippet rendered from
    // it, is the actual signal for warnings/notices/etc. Thrown Exceptions
    // (see App::handleHttp's catch block) pass a real, useful trace instead.
    abort("{$errorType}: {$message}", 500, $file, $line, null);
    // Stop execution for fatal errors
    if ($severity === E_ERROR || $severity === E_CORE_ERROR || $severity === E_COMPILE_ERROR) {
        exit(1);
    }

    return true;
}

function getCodeContext(string $file, int $errorLine, int $contextLines = 3) {
    if (!file_exists($file)) {
        return [];
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES);
    $startLine = max(0, $errorLine - $contextLines - 1);
    $endLine = min(count($lines) - 1, $errorLine + $contextLines - 1);

    $context = [];
    for ($i = $startLine; $i <= $endLine; $i++) {
        $context[] = [
            'number' => $i + 1,
            'content' => $lines[$i] ?? '',
            'isError' => ($i + 1) === $errorLine
        ];
    }

    return $context;
}


function getErrorMessages() {

    return [
        '403' => [
            'title' => 'Forbidden',
            'client_message' => 'The server refused to fulfill the request.',
            'developer_message' => 'Access to this resource is forbidden. Check file permissions or authentication requirements.'
        ],
        '404' => [
            'title' => 'Not Found',
            'client_message' => 'The page requested was not found on this server.',
            'developer_message' => 'The requested URL was not found on this server. Check the URL or routing configuration.'
        ],
        '405' => [
            'title' => 'Method Not Allowed',
            'client_message' => 'The method specified is not allowed for this resource.',
            'developer_message' => 'The HTTP method used is not supported for this endpoint. Check allowed methods in your route configuration.'
        ],
        '408' => [
            'title' => 'Request Timeout',
            'client_message' => 'Your browser failed to send a request in the time allowed.',
            'developer_message' => 'The request took too long to process. Check for slow database queries or external API calls.'
        ],
        '500' => [
            'title' => 'Internal Server Error',
            'client_message' => 'The request was unsuccessful due to an unexpected condition.',
            'developer_message' => 'An unexpected error occurred. Check error logs for more details.'
        ],
        '502' => [
            'title' => 'Bad Gateway',
            'client_message' => 'The server received an invalid response from the upstream server.',
            'developer_message' => 'The upstream server returned an invalid response. Check if all services are running properly.'
        ],
        '504' => [
            'title' => 'Gateway Timeout',
            'client_message' => 'The upstream server failed to respond in time.',
            'developer_message' => 'The upstream server timed out. Check if the service is overloaded or unresponsive.'
        ],
        // Database specific errors
        '2002' => [
            'title' => 'Database Connection Error',
            'client_message' => 'A database connection error occurred.',
            'developer_message' => 'Cannot connect to MySQL server. Check database host, port, and credentials.'
        ],
        '1049' => [
            'title' => 'Database Not Found',
            'client_message' => 'A database error occurred.',
            'developer_message' => 'The specified database does not exist. Check database name in configuration.'
        ],
        '1045' => [
            'title' => 'Access Denied',
            'client_message' => 'A database authentication error occurred.',
            'developer_message' => 'Access denied for the specified user. Check database username and password.'
        ]
    ];
}
