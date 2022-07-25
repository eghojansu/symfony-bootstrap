<?php

namespace App\Extension\Controller;

use App\Extension\Utils;
use App\Extension\ControllerContext;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

abstract class ActionController implements Action, ServiceSubscriberInterface
{
    public function __construct(
        private ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return static::subscribe();
    }

    public function getName(): string
    {
        return Utils::classToName(static::class);
    }

    public function handle(ControllerContext $context, Request $request): Response
    {
        if (is_callable($this)) {
            return $this(...$this->getArguments(compact('request', 'context')));
        }

        throw new \LogicException(sprintf('Method not implemented %s', __METHOD__));
    }

    protected function getArguments(array $args): array
    {
        return array_map(
            fn (string $name) => $args[$name] ?? (
                $this->container->has($name) ? $this->container->get($name) : null
            ),
            Utils::merge(
                $this->getArgumentList(),
                $this->getExtraArgumentList(),
                array_keys(static::subscribe()),
            ),
        );
    }

    protected function getArgumentList(): array|string
    {
        return 'context,request';
    }

    protected function getExtraArgumentList(): array|string|null
    {
        return null;
    }

    protected static function subscribe(): array
    {
        return array();
    }
}