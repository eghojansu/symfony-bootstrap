<?php

namespace App\DataFixtures;

use App\Entity\Csuser;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $hasher,
    ) {}

    public function load(ObjectManager $manager): void
    {
        $root = Csuser::create('su', active: true, roles: 'ROLE_ROOT');
        $root->setPassword($this->hasher->hashPassword($root, 'admin123'));

        $manager->persist($root);
        $manager->flush();
    }
}
