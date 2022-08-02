<?php

namespace App\Extension\Crud\Concern;

interface WithColumns
{
    public function getColumns(string $action, array $columns): array;
}