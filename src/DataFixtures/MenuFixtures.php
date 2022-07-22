<?php

namespace App\DataFixtures;

use App\Entity\Csmenu;
use App\Extension\Utils;
use App\Extension\RBAC\Menu;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class MenuFixtures extends Fixture
{
    use FixtureTrait;

    public function __construct(private Menu $menu)
    {}

    public function load(ObjectManager $manager): void
    {
        Utils::walk(
            $this->menu->toEntities(
                self::getData('menu_db') + self::getData('menu_top'),
            ),
            static fn (Csmenu $menu) => $manager->persist($menu),
        );

        $manager->flush();
    }
}
