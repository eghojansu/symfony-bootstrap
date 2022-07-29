<?php

namespace App\Extension\Crud;

use App\Extension\Utils;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

abstract class BaseConfigurator implements ConfiguratorInterface, ServiceSubscriberInterface
{
    public function __construct(private ContainerInterface $container)
    {}

    public static function getSubscribedServices(): array
    {
        return static::subscribe();
    }

    public static function getDefaultIndexName(): string
    {
        return str_replace('_configurator', '', Utils::classToName(static::class));
    }

    protected static function subscribe(): array
    {
        return array();
    }
}