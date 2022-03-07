<?php

namespace Clive\Core\Mantle;

class Session {

    public static function make($name, $value) {
        if (isset($_SESSION[$name])) {
            return;
        }
        return $_SESSION[$name] = $value;
    }
    public static function get($name) {
        if (!isset($_SESSION[$name])) {
            return;
        }
        return $_SESSION[$name];
    }

    public static function unset($key){
        if(array_key_exists($key, $_SESSION)){
         unset($_SESSION[$key]);
        }
        return;
       
    }
    public static function destroy(){
        session_destroy();
        return;
    }
}
