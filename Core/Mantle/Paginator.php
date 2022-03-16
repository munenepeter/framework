<?php
namespace App\Core\Mantle;

use App\Core\Mantle\Request;

class Paginator{
    private static $per_page;
    public static function paginate(array $data, int $per_page = 10){
        self::$per_page = $per_page;
        return array_slice($data, $per_page * intval($_GET['page']) - $per_page, $per_page);
    }

    public static function showLinks($data){

        $max_pages = ceil(count($data) / self::$per_page);

        if($_GET['page'] > 1)
        echo  '<a href="/'.Request::uri().'?page='.($_GET['page']-1).'"> Previous </a>';
        if($_GET['page'] < $max_pages)
        echo  '<a href="/'.Request::uri().'?page='.($_GET['page']+1).'"> Next </a>';  
    }

}
// Paginator::paginate($users, 10);
// Paginator::showLinks($users);


/* 
class Paginator{
    private static $per_page;
    public static $pg = 2;
    public static function paginate(array $data, int $per_page = 2){
        self::$per_page = $per_page;
        return array_slice($data, $per_page * intval(self::$pg) - $per_page, $per_page, 1);
       }

    public static function showLinks($data){

        $max_pages = ceil(count($data) / self::$per_page);
        
        if(self::$pg > 1)
        echo  '<a href="/rr?page='.(self::$pg-1).'"> Previous </a>'.PHP_EOL;
        if(self::$pg < $max_pages)
        echo  '<a href="/rr?page='.(self::$pg+1).'"> Next </a>';  
    }

}

$users = array("1", "2", "3", "4", "5", "6", "7", "8");
//var_dump($users);

var_dump(Paginator::paginate($users,2));
Paginator::showLinks($users);

*/     