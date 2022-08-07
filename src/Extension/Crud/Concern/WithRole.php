<?php

namespace App\Extension\Crud\Concern;

interface WithRole
{
    public function getRole(string $action): string|null;
}