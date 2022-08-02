<?php

namespace App\Extension\Crud\Concern;

interface WithContext
{
    public function getContext(string $action, array $context): array;
}