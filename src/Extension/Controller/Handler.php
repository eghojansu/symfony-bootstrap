<?php

namespace App\Extension\Controller;

use App\Extension\Utils;
use App\Extension\ControllerContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('controller.service_arguments')]
class Handler
{
    public function __construct(
        private ControllerContext $context,
        #[TaggedIterator('app.action_controller')]
        private iterable $actions,
    ) {}

    public function handle(Request $request, array $static): Response
    {
        if (isset($static['action'])) {
            return $this->getAction($static['action'])->handle($this->context, $request);
        }

        return $this->context->render($static['view']);;
    }

    private function getAction(string $name): Action
    {
        $action = Utils::find(
            $this->actions,
            static fn (Action $action) => $name === $action->getName(),
        );

        if (!$action) {
            throw new \LogicException(sprintf('Action not found: %s', $name));
        }

        return $action;
    }
}