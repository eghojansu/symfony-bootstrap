<?php

namespace App\Extension\ORM\Generator;

use App\Extension\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AbstractIdGenerator;

class UniqidGenerator extends AbstractIdGenerator
{
    public function generateId(EntityManagerInterface $em, $entity)
    {
        return Utils::random(8);
    }
}