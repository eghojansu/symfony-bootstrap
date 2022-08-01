<?php

namespace App\Extension\Crud;

use App\Extension\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Config\Loader\Loader as BaseLoader;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('routing.loader')]
final class Loader extends BaseLoader
{
    private $isLoaded = false;

    public function __construct(private EntityManagerInterface $em)
    {}

    public function load(mixed $resource, ?string $type = null): mixed
    {
        if ($this->isLoaded) {
            throw new \RuntimeException('Static page loader is already loaded');
        }

        $this->isLoaded = true;

        return Utils::reduce(
            $this->em->getMetadataFactory()->getAllMetadata(),
            function (RouteCollection $routes, ClassMetadata $meta) {
                $resources = $meta->getReflectionClass()->getAttributes(Resource::class);
                $resource = $resources ? $resources[0]->newInstance() : null;

                if ($resource?->enabled) {
                    $routes->addCollection(
                        (new Builder($meta, $resource, Resource::PATH_PREFIX))->getRoutes(),
                    );
                }

                return $routes;
            },
            new RouteCollection(),
        );
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return 'crud' === $type;
    }
}