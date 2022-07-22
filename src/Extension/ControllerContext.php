<?php

namespace App\Extension;

use Twig\Environment;
use App\Entity\Csuser;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class ControllerContext implements ServiceSubscriberInterface
{
    public function __construct(
        private ContainerInterface $container,
        private TokenStorageInterface $tokenStorage,
        private RequestStack $requestStack,
    ) {}

    public static function getSubscribedServices(): array
    {
        return array(
            'twig' => '?' . Environment::class,
        );
    }

    public function request(): Request
    {
        return $this->requestStack->getCurrentRequest();
    }

    public function user(): Csuser|null
    {
        return $this->tokenStorage->getToken()?->getUser();
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