<?php

namespace App\Models;

use App\Core\Mantle\App;

class Model {


    public static function getInstance() {
        $model_name = new static;
        return $model_name;
    }

    private static function tableName() {

        //get table name form the model
        $tableName  = get_class(static::getInstance());
        //convert to lowercase and pluralize it
        $tableName  = plural(strtolower($tableName), 2);
        // remove the namespace
        $tableName = substr($tableName, strrpos($tableName, '\\')+1);

        return $tableName;
        //users
    }

    public static function create(array $data) {

        App::get('database')->insert(static::tableName(), $data);
        //User::create(['name'=>'peter']);
    }
    public static function update(array $data) {

        App::get('database')->update(static::tableName(), $data);
        //User::update(['name'=>'peter', 'id' => 23]);
    }
    public static function all() {
	 //Returns all the records in the db for a certain  model/table

       return App::get('database')->selectAll(static::tableName());
        //User::all();
    }
    public static function where(array $data, array $condition) {
     //Returns all the records in the db for a certain  model/table
    
      return  App::get('database')->selectWhere(static::tableName(), $data, $condition);
        //User::all();
    }
}
