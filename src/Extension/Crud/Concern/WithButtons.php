<?php

namespace App\Extension\Crud\Concern;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;

interface WithButtons
{
    public function getButtons(Security $security, UrlGeneratorInterface $urlGenerator): array;
}