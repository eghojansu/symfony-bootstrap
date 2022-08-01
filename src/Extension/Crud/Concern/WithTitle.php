<?php

namespace App\Extension\Crud\Concern;

interface WithTitle
{
    public function getTitle(string $action, string $title): string;
}