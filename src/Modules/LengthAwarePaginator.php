<?php

namespace Tabel\Modules;

use Tabel\Core\Request;

/**
 * LengthAwarePaginator
 *
 * Value object returned by Model::paginate() and Paginator::make().
 * Carries the page data and all metadata needed to render links.
 */
class LengthAwarePaginator {

    /** @var array The items for the current page. */
    public readonly array $items;

    /** @var int Total number of rows across all pages. */
    public readonly int $total;

    /** @var int Items per page. */
    public readonly int $perPage;

    /** @var int Current page number (1-indexed). */
    public readonly int $currentPage;

    /** @var int Total number of pages. */
    public readonly int $lastPage;

    /** @var int First item number on this page (1-indexed). */
    public readonly int $from;

    /** @var int Last item number on this page (1-indexed). */
    public readonly int $to;

    /** @var bool Whether there is a previous page. */
    public readonly bool $hasPrev;

    /** @var bool Whether there is a next page. */
    public readonly bool $hasNext;

    public function __construct(array $items, int $total, int $perPage, int $currentPage) {
        $lastPage    = max(1, (int) ceil($total / $perPage));
        $currentPage = max(1, min($currentPage, $lastPage));

        $offset = $perPage * ($currentPage - 1);
        $count  = count($items);

        $this->items       = $items;
        $this->total       = $total;
        $this->perPage     = $perPage;
        $this->currentPage = $currentPage;
        $this->lastPage    = $lastPage;
        $this->from        = $count > 0 ? $offset + 1 : 0;
        $this->to          = $count > 0 ? $offset + $count : 0;
        $this->hasPrev     = $currentPage > 1;
        $this->hasNext     = $currentPage < $lastPage;
    }

    /**
     * Return the items for the current page.
     */
    public function items(): array {
        return $this->items;
    }

    /**
     * Render previous/next links.
     *
     * @param  string $baseUri  Defaults to the current request URI.
     * @return string  HTML string with Previous / Next anchor tags.
     */
    public function links(string $baseUri = ''): string {
        if ($baseUri === '') {
            $baseUri = '/' . Request::uri();
        }

        $html = '';

        if ($this->hasPrev) {
            $html .= '<a class="p-2 text-blue-500" href="'
                . $baseUri . '?page=' . ($this->currentPage - 1)
                . '"> &laquo; Previous </a>';
        }

        if ($this->hasNext) {
            $html .= '<a class="p-2 text-blue-500" href="'
                . $baseUri . '?page=' . ($this->currentPage + 1)
                . '"> Next &raquo; </a>';
        }

        return $html;
    }

    /**
     * Render a numbered page list with a sliding window.
     *
     * @param  int    $window    How many pages to show either side of current.
     * @param  string $baseUri
     * @return string  HTML string.
     */
    public function numberedLinks(int $window = 2, string $baseUri = ''): string {
        if ($baseUri === '') {
            $baseUri = '/' . Request::uri();
        }

        if ($this->lastPage <= 1) {
            return '';
        }

        $start = max(1, $this->currentPage - $window);
        $end   = min($this->lastPage, $this->currentPage + $window);

        $html = '<nav class="flex gap-1">';

        if ($this->hasPrev) {
            $html .= $this->pageLink($this->currentPage - 1, '&laquo;', $baseUri);
        }

        if ($start > 1) {
            $html .= $this->pageLink(1, '1', $baseUri);
            if ($start > 2) {
                $html .= '<span class="p-2 text-gray-400">&hellip;</span>';
            }
        }

        for ($p = $start; $p <= $end; $p++) {
            $html .= $this->pageLink($p, (string) $p, $baseUri, $p === $this->currentPage);
        }

        if ($end < $this->lastPage) {
            if ($end < $this->lastPage - 1) {
                $html .= '<span class="p-2 text-gray-400">&hellip;</span>';
            }
            $html .= $this->pageLink($this->lastPage, (string) $this->lastPage, $baseUri);
        }

        if ($this->hasNext) {
            $html .= $this->pageLink($this->currentPage + 1, '&raquo;', $baseUri);
        }

        $html .= '</nav>';

        return $html;
    }

    private function pageLink(int $page, string $label, string $baseUri, bool $active = false): string {
        $cls = $active
            ? 'p-2 text-white bg-blue-500 rounded'
            : 'p-2 text-blue-500 hover:underline';

        return '<a class="' . $cls . '" href="' . $baseUri . '?page=' . $page . '">' . $label . '</a>';
    }

    /**
     * Serialise to array - useful for JSON API responses.
     */
    public function toArray(): array {
        return [
            'data'         => $this->items,
            'total'        => $this->total,
            'per_page'     => $this->perPage,
            'current_page' => $this->currentPage,
            'last_page'    => $this->lastPage,
            'from'         => $this->from,
            'to'           => $this->to,
        ];
    }

    public function toJson(): string {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
