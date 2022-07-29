<?php

namespace App\Extension\Crud;

use App\Extension\ControllerContext;
use App\Extension\Utils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('controller.service_arguments')]
class Handler
{
    public function __construct(
        private ControllerContext $context,
        #[TaggedIterator('app.crud')]
        private iterable $configurators,
    ) {}

    public function handle(
        Request $request,
        array $crud,
    ): Response {
        $config = $crud['config'] ? Utils::find(
            $this->configurators,
            static fn (...$args) => $args[1] === $crud['config'],
        ) : null;

        return match (true) {
            default => new Response(sprintf('Resource not found: %s', $crud['entity']), 404),
        };
    }
}