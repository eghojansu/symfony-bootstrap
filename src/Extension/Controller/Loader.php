<?php

namespace App\Extension\Controller;

use App\Extension\Utils;
use Symfony\Component\Yaml\Yaml;
use App\Extension\ControllerContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Config\Loader\Loader as BaseLoader;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('routing.loader')]
final class Loader extends BaseLoader
{
    const CONTROLLER = 'App\\Extension\\Controller\\Handler::handle';

    private $isLoaded = false;

    public function __construct(
        private ControllerContext $context,
        #[Autowire('%kernel.project_dir%/config')]
        private string $projectDir,
    ) {}

    public function load(mixed $resource, ?string $type = null): mixed
    {
        if ($this->isLoaded) {
            throw new \RuntimeException('Static page loader is already added');
        }

        $this->isLoaded = true;

        return Utils::reduce(
            Yaml::parseFile($this->projectDir . '/' . $resource),
            function (RouteCollection $routes, $setup, string $path) {
                $rule = $this->prepareRoute($path, $setup);
                $name = $rule['name'] ?? self::createRouteName($path, $rule['view']);

                $routes->add($name, $this->createRoute($rule));

                return $routes;
            },
            new RouteCollection(),
        );
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return 'action_controller' === $type && str_ends_with($resource, '.yaml');
    }

    private function createRoute(array $rule): Route
    {
        $args = Utils::extract(
            $rule,
            array(
                'path',
                'defaults' => array(),
                'requirements' => array(),
                'options' => array(),
                'host',
                'schemes' => array(),
                'methods' => array(),
                'condition',
            ),
            $static,
        );
        list($path, $defaults) = array_splice($args, 0, 2);

        $defaults['_controller'] = self::CONTROLLER;

        return new Route($path, $defaults + compact('static'), ...$args);
    }

    private function prepareRoute(string $path, array|string|null $setup): array
    {
        $rule = array();
        $view = $path;
        $methods = array('GET');

        if (is_string($setup)) {
            $view = $setup;
        } elseif (is_array($setup)) {
            $view = $setup['view'] ?? $view;
            $methods = $setup['methods'] ?? $methods;
            $rule = $setup;
        }

        if (!str_ends_with($view, '.twig')) {
            $view = str_replace('.', '/', $view) . '.html.twig';
        }

        return array(
            'path' => (str_starts_with($path, '/') ? '' : '/') . $path,
            'view' => (str_starts_with($view, '/') ? '' : '/') . $view,
            'methods' => $methods,
        ) + $rule;
    }

    private static function createRouteName(string $path, string $view): string
    {
        $name = 'ac' . str_replace(array('/', '.'), '_', strlen($path) < 2 ? substr($view, 0, strrpos($view, '.')) : $path);

        if (preg_match('//u', $name)) {
            return function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);
        }

        return $name;
    }
}