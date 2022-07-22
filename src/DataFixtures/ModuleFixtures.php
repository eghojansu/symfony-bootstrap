<?php

namespace App\DataFixtures;

use App\Entity\Csmod;
use App\Entity\Csperm;
use App\Extension\Utils;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ModuleFixtures extends Fixture
{
    use FixtureTrait;

    public function load(ObjectManager $manager): void
    {
        Utils::walk(
            self::getEntities(),
            static fn ($entity) => $manager->persist($entity),
        );

        $manager->flush();
    }

    private static function getEntities(): array
    {
        $modules = self::getData('modules');
        $entities = array();

        array_walk(
            $modules,
            function (array $module, string $id) use (&$entities) {
                $mod = self::createModule($id, $module['name']);
                $entities[] = $mod;

                array_walk(
                    $module['permissions'],
                    function (string $permission, string $id) use ($mod, &$entities) {
                        $perm = self::createPermission($id, $permission);
                        $entities[] = $perm;

                        $mod->addPermission($perm);
                    },
                );
            },
        );

        return $entities;
    }

    private static function createModule(
        string $id,
        string $description,
    ): Csmod {
        $module = new Csmod();
        $module->setId($id);
        $module->setDescription($description);

        return $module;
    }

    private static function createPermission(
        string $id,
        string $description,
    ): Csperm {
        $perm = new Csperm();
        $perm->setId($id);
        $perm->setDescription($description);

        return $perm;
    }
}
