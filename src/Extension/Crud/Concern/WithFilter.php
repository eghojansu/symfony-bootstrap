<?php

namespace App\Extension\Crud\Concern;

use Symfony\Component\HttpFoundation\Request;

interface WithFilter
{
    public function getFilter(string $action, Request $request): array;
}