<?php

namespace App\Extension\Crud\Concern;

interface WithTemplate
{
    public function getTemplate(string $action): string;
}