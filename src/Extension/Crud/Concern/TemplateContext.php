<?php

namespace App\Extension\Crud\Concern;

interface TemplateContext
{
    public function getTemplateContext(string $action): array;
}