<?php

namespace App\Extension\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface Action
{
    public function getName(): string;
    public function handle(Request $request): Response;
}