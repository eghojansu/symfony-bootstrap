<?php

namespace App\Extension\Crud;

use App\Extension\Utils;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

abstract class AbstractConfigurator implements Configurator, ServiceSubscriberInterface
{
    public function __construct(private ContainerInterface $container)
    {}

    public static function getSubscribedServices(): array
    {
        return static::subscribe();
    }

    public static function getDefaultIndexName(): string
    {
        if (str_ends_with(static::class, 'Configurator')) {
            return Utils::classToName(substr(static::class, 0, -12));
        }

        return Utils::classToName(static::class);
    }

    protected static function subscribe(): array
    {
        return array();
    }
}