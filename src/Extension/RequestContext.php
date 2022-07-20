<?php

namespace App\Extension;

use Twig\Environment;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class RequestContext implements ServiceSubscriberInterface
{
    public function __construct(private ContainerInterface $container)
    {}

    public static function getSubscribedServices(): array
    {
        return array(
            'twig' => '?' . Environment::class,
        );
    }

    /**
     * Returns a rendered view.
     */
    public function renderView(string $view, array $parameters = null): string
    {
        if (!$this->container->has('twig')) {
            throw new \LogicException('You cannot use the "renderView" method if the Twig Bundle is not available. Try running "composer require symfony/twig-bundle".');
        }

        return $this->container->get('twig')->render($view, $parameters ?? array());
    }

    /**
     * Renders a view.
     */
    public function render(string $view, array $parameters = null, Response $response = null): Response
    {
        return ($response ?? new Response())->setContent($this->renderView($view, $parameters));
    }
}