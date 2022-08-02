<?php

namespace App\Extension\Crud\Concern;

interface WithSearchable
{
    public function getSearchable(): array|bool;
}