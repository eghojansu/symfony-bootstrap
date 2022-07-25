<?php

namespace App\Controller;

use App\Extension\ApiContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @property ApiContext $api
 */
abstract class Controller extends AbstractController
{
    public static function getSubscribedServices(): array
    {
        return self::subscribe() + parent::getSubscribedServices() + array(
            'api' => ApiContext::class,
        );
    }

    protected static function subscribe(): array
    {
        return array();
    }

    public function __get($name)
    {
        if (method_exists($this, $get = '_' . ltrim($name, '_'))) {
            return $this->$get();
        }

        return $this->container->get($name);
    }
}