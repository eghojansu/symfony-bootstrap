<?php

namespace App\Extension\Crud\Concern;

interface ModifyPagination
{
    public function getPaginationModifier(): \Closure;
}