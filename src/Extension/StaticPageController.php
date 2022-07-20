<?php

namespace App\Extension;

use App\Extension\RequestContext;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Yaml\Yaml;

#[AsEventListener(
    event: KernelEvents::REQUEST,
    priority: 33, // right before router
)]
class StaticPageController
{
    /** @var array */
    private $routes = array();

    public function __construct(
        private RequestContext $context,
        #[TaggedIterator('app.action_controller')]
        private iterable $actions,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
        $this->loadRoutes();
    }

    public function __invoke(RequestEvent $event)
    {
        $path = $event->getRequest()->getPathInfo();
        $base = $this->routes[$path] ?? $this->defaultSetup($path);

        if (!$base) {
            return;
        }

        $setup = match(true) {
            is_string($base) => $this->defaultView($base),
            !isset($base['view']) => $base + $this->defaultView($path),
            default => $base,
        };
        $response = match(true) {
            // isset($setup['action']) => $this->create
        };

        $event->setResponse(new Response(var_export($this->routes, true)));
        $event->stopPropagation();
    }

    private function loadRoutes(): void
    {
        $this->routes = Yaml::parseFile($this->projectDir . '/config/static_routes.yaml');
    }

    private function defaultSetup(string $path): array|null
    {
        return array_key_exists($path, $this->routes) ? $this->defaultView($path) : null;
    }

    private function defaultView(string $path): array
    {
        $view = str_replace('.', '/', $path) . '.html.twig';

        return compact('view');
    }
}