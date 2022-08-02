<?php

namespace App\Extension\Crud\Concern;

interface WithColumnsIgnore
{
    public function getIgnoredColumns(string $action): array;
    public function mergeIgnoredColumn(string $action): bool;
}