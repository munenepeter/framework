<?php

namespace Tabel\Modules;




class Paginator {

    public static $per_page;
    public static $totalCount;
    
    public static $start;
    public static $end;

    public static function getPage() {
        return (!isset($_GET['page'])) ? 1 : (int)$_GET['page'];
    }

    public static function paginate(array $data, int $per_page = 10) {

        self::$per_page = $per_page;

        $paginated_data = array_slice($data, $per_page * intval(self::getPage()) - $per_page, $per_page);
        self::$start = (int)(implode('',array_keys($paginated_data,reset($paginated_data))))+(self::getPage());
        self::$end =  (int)(implode('',array_keys($paginated_data,end($paginated_data))))+1; 
        return $paginated_data;
    }

    public static function showLinks(array $data) {

        self::$totalCount = count($data);

        $max_pages = ceil(self::$totalCount / self::$per_page);

        if (self::getPage() > 1)
            echo  '<a class="p-2 text-blue-500" href="/' . Request::uri() . '?page=' . (self::getPage() - 1) . '"> Previous </a>';
        if (self::getPage() < $max_pages)
            echo  '<a class="p-2 text-blue-500" href="/' . Request::uri() . '?page=' . (self::getPage() + 1) . '"> Next </a>';
    }
}
// Paginator::paginate($users, 10);
// Paginator::showLinks($users);
