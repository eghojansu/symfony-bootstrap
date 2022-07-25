<?php

namespace App\Extension\Controller;

use App\Extension\ControllerContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface Action
{
    public function getName(): string;
    public function handle(ControllerContext $context, Request $request): Response;
}