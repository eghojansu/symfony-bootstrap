<?php

namespace App\Extension\Crud\Concern;

use App\Extension\Pagination;
use Symfony\Component\HttpFoundation\Request;

interface Template
{
    public function template(string $action): string;
}