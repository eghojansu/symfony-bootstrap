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
    private $defaults;

    public function __construct(
        private ClassMetadata $entityMetadata,
        private Resource $crud,
        private string|null $basePath,
    ) {
        $entity = Utils::className($entityMetadata->getName());

        $this->routes = new RouteCollection();
        $this->name = $crud->name ?? Utils::caseSnake($entity);
        $this->path = $crud->path ?? '/' . Utils::caseKebab($entity);
        $this->defaults = array(
            'crud' => array(
                'config' => $crud->config,
                'entity' => $entityMetadata->getName(),
                'jsController' => $crud->jsController,
                'name' => $this->name,
                'template' => $crud->template,
                'title' => $crud->title,
            ),
        );
    }

    public function getRoutes(): RouteCollection
    {
        return $this->routes;
    }

    public function add(
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
                $this->basePath . $this->path . $path,
                array_replace_recursive(
                    $this->defaults,
                    $defaults ?? array(),
                    array(
                        '_controller' => Controller::class . '::' . $action,
                        'crud' => array('action' => $action),
                    ),
                ),
                $requirements ?? array(),
                $options ?? array(),
                $host,
                $schemes ?? array(),
                $methods ?? array(),
                $condition,
            ),
        );
    }
}