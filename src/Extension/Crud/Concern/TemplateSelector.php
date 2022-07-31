<?php

namespace App\Extension\Crud\Concern;

interface TemplateSelector
{
    public function getSelectedTemplate(string $action): string;
}