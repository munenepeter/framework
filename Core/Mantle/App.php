<?php

namespace Tabel\Core\Mantle;

class App{
  public static $registry = [];

  public static function bind($key, $value){
     self::$registry[$key] = $value;
  }
  public static function get($key){
      if(!array_key_exists($key, static::$registry)){
          throw new \Exception("The {$key} was not found is this container", 500); 
      }
      return self::$registry[$key];
  }

}
// App::bind('database', new Database);
// App::get('database')->insert;