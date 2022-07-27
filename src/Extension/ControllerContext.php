<?php

namespace App\Extension;

use Twig\Environment;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;

final class ControllerContext
{
    public function __construct(
        private Environment $twig,
        private RequestStack $requestStack,
    ) {}

    public function request(): Request
    {
        return $this->requestStack->getCurrentRequest();
    }

    /**
     * Returns a rendered view.
     */
    public function renderView(string $view, array $parameters = null): string
    {
        return $this->twig->render($view, $parameters ?? array());
    }

    /**
     * Renders a view.
     */
    public function render(string $view, array $parameters = null, Response $response = null): Response
    {
        return ($response ?? new Response())->setContent(
            $this->renderView($view, $parameters),
        );
    }
}