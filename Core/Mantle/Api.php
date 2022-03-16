<?php

namespace App\Core\Mantle;

class API{
 
    public static function call(string $url){
        $news = file_get_contents($url);
        return json_encode($news);
    }
}
//API::call("https://"); // json
