App<?php

namespace Clive\Core\Mantle;

class App{
  public static array $registry = [];

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