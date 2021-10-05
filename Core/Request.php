<?php

namespace App\Core;


class Request {
    //get the current URI
    public static function uri() {

        return trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    }
    //get the method as requested by the router
    public static function method() {

        return $_SERVER['REQUEST_METHOD'];
    }
    //get all the post data
    public function getFormData(){
        
        //check if the data was post data
        if(strtolower(self::method()) === 'post'{
            
             return $_POST;
            
        }
       if(strtolower(self::method()) === 'get'{
           
           return $_GET;
       }
    }
    /**
    * Get all the get data provided you give the name
    *
    * @param string $name this is the name of the input/select
    *
    * @return string the data from the $_GET Superglobal
    */      
    public static function get(string $name){
       if(!isset($_GET[$name])){
           return;
       }
       return $_GET[$name];
    
    }
    //validate and create an error bag
    private function validate($data){
    
    foreach($data as $key => $value ){
        
        if(empty($value)){
            
            echo "The {$key} field is Required \n";
            
         }elseif($value){

           $data = [];
           $data[$key] = $value; 
           }
       }
     return $data;
    }
        
}
