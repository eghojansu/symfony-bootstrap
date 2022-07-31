<?php

namespace App\Extension\Crud\Concern;

interface ColumnsIgnore
{
    public function getIgnoredColumns(string $action): array|null;
    public function getIgnoredColumnsAppend(string $action): array|null;
}