<?php

namespace Tabel\Modules;

/**
 * Paginator
 *
 * Static utility for paginating plain PHP arrays (data already in memory).
 *
 * For database-backed pagination use Model::paginate() - it pushes
 * LIMIT/OFFSET into SQL so only the rows for the requested page are
 * ever fetched from the database.
 */
class Paginator {

    /**
     * Paginate an in-memory array.
     *
     * Best for small datasets that are already loaded (API responses, CSVs…).
     * For database queries always prefer Model::paginate() instead.
     *
     * @param  array $data
     * @param  int   $perPage
     * @return LengthAwarePaginator
     */
    public static function paginate(array $data, int $perPage = 10): LengthAwarePaginator {
        $currentPage = self::currentPage();
        $total       = count($data);
        $lastPage    = max(1, (int) ceil($total / $perPage));
        $currentPage = max(1, min($currentPage, $lastPage));
        $offset      = $perPage * ($currentPage - 1);
        $items       = array_slice($data, $offset, $perPage);

        return new LengthAwarePaginator($items, $total, $perPage, $currentPage);
    }

    /**
     * Resolve the current page number from the request ($_GET['page']).
     */
    public static function currentPage(): int {
        return isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    }
}
