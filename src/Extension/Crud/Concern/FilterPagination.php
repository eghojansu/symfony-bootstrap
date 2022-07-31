<?php

namespace App\Extension\Crud\Concern;

use Symfony\Component\HttpFoundation\Request;

interface FilterPagination
{
    public function getPaginationFilter(Request $request): array;
}