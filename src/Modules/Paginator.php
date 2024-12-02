<?php

namespace Tabel\Modules;

use Tabel\Core\Request;

class Paginator {
    public static $per_page;
    public static $totalCount;
    public static $start;
    public static $end;

    /**
     * Get the current page number from the request
     * 
     * @return int
     */
    public static function getPage(): int {
        return isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    }

    /**
     * Paginate the given data
     * 
     * @param array $data Data to paginate
     * @param int $per_page Number of items per page
     * @return array Paginated data
     */
    public static function paginate(array $data, int $per_page = 10): array {
        self::$per_page = $per_page;
        $currentPage = self::getPage();
        $offset = $per_page * ($currentPage - 1);

        $paginated_data = array_slice($data, $offset, $per_page);

        if (empty($paginated_data)) {
            self::$start = self::$end = 0;
        } else {
            self::$start = $offset + 1;
            self::$end = $offset + count($paginated_data);
        }

        return $paginated_data;
    }

    /**
     * Display pagination links
     * 
     * @param array $data Data to paginate
     * @return void
     */
    public static function showLinks(array $data): void {
        self::$totalCount = count($data);
        $max_pages = ceil(self::$totalCount / self::$per_page);
        $currentPage = self::getPage();

        if ($currentPage > 1) {
            echo '<a class="p-2 text-blue-500" href="/' . Request::uri() . '?page=' . ($currentPage - 1) . '"> Previous </a>';
        }
        if ($currentPage < $max_pages) {
            echo '<a class="p-2 text-blue-500" href="/' . Request::uri() . '?page=' . ($currentPage + 1) . '"> Next </a>';
        }
    }
}
