<?php

namespace App\Extension;

use Doctrine\ORM\Tools\Pagination\Paginator;

class Pagination implements \JsonSerializable
{
    public $total = 0;
    public $pages = 0;
    public $prev = 0;
    public $next = 0;

    public function __construct(
        public Paginator $items,
        public int $page,
        public int $size,
    ) {
        $this->total = count($items);
        $this->pages = ceil($this->total / $size);
        $this->prev = max(1, $page - 1);
        $this->next = min($this->pages, $page + 1);
    }

    public function jsonSerialize(): mixed
    {
        return array(
            'items' => $this->items,
            'next' => $this->next,
            'prev' => $this->prev,
            'size' => $this->size,
            'total' => $this->total,
            'page' => $this->page,
            'pages' => $this->pages,
        );
    }
}