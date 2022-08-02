<?php

namespace App\Extension\Crud\Concern;

interface WithModifier
{
    public function getModifier(): \Closure;
}