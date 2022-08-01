<?php

namespace App\Extension\Crud\Concern;

interface SearchablePagination
{
    public function getPaginationSearchable(): array|bool;
}