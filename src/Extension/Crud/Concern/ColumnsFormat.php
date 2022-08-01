<?php

namespace App\Extension\Crud\Concern;

interface ColumnsFormat
{
    public function getColumnsFormat(string $action, array $columns): array;
}