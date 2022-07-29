<?php

namespace App\Extension\Crud\Concern;

use App\Extension\Pagination;
use Symfony\Component\HttpFoundation\Request;

interface Index
{
    public function paginate(Request $request): Pagination;
}