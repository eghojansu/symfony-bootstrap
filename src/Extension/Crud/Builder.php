<?php

namespace App\Extension\Crud;

use App\Extension\Utils;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Doctrine\Persistence\Mapping\ClassMetadata;

final class Builder
{
    /** @var RouteCollection */
    private $routes;
    private $name;
    private $path;

    public function __construct(
        private ClassMetadata $entityMetadata,
        private Resource $crud,
        string|null $basePath,
    ) {
        $this->routes = new RouteCollection();
        $this->name = $crud->name ?? Utils::classToName($entityMetadata->getName());
        $this->path = $basePath . ($crud->path ?? '/' . Utils::classToName($entityMetadata->getName(), 'kebab'));

        $this->build();
    }

    public function getRoutes(): RouteCollection
    {
        return $this->routes;
    }

    private function build(): void
    {
        $this->indexable() && $this->add(Resource::ACTION_INDEX, 'GET');
    }

    private function add(
        string $action,
        string|array $methods = null,
        string $path = null,
        array $defaults = null,
        array $requirements = null,
        array $options = null,
        string $host = null,
        string|array $schemes = null,
        string $condition = null,
    ): void {
        $this->routes->add(
            $this->name . '_' . $action,
            new Route(
                $this->path . $path,
                $this->createDefaults($action, $defaults),
                $requirements ?? array(),
                $options ?? array(),
                $host,
                $schemes ?? array(),
                $methods ?? array(),
                $condition,
            ),
        );
    }

    private function createDefaults(string $action, array $overrides = null): array
    {
        return array_replace_recursive(
            array(
                '_controller' => Controller::class . '::' . $action,
                '_crud' => Accessor::toRouteArgument(
                    $this->crud,
                    $action,
                    $this->name,
                    $this->entityMetadata->getName(),
                ),
            ),
            $overrides ?? array(),
        );
    }

    private function indexable(): bool
    {
        return $this->crud->enabled && -1 < strpos($this->crud->enabled, 'I');
    }

    private function creatable(): bool
    {
        return $this->crud->enabled && -1 < strpos($this->crud->enabled, 'C');
    }

    private function viewable(): bool
    {
        return $this->crud->enabled && -1 < strpos($this->crud->enabled, 'R');
    }

    private function editable(): bool
    {
        return $this->crud->enabled && -1 < strpos($this->crud->enabled, 'U');
    }

    private function removeable(): bool
    {
        return $this->crud->enabled && -1 < strpos($this->crud->enabled, 'D');
    }

    private function restorable(): bool
    {
        return $this->crud->enabled && -1 < strpos($this->crud->enabled, 'O');
    }

    private function destroyable(): bool
    {
        return $this->crud->enabled && -1 < strpos($this->crud->enabled, 'P');
    }
}