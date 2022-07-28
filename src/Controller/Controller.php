<?php

namespace App\Controller;

use App\Entity\Csuser;
use App\Service\Account;
use App\Extension\ApiContext;
use App\Extension\ControllerContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @property Csuser $user
 * @property Account $account
 * @property ApiContext $api
 * @property ControllerContext $context
 */
abstract class Controller extends AbstractController
{
    protected function view(string $view, array $parameters = null, Response $response = null): Response
    {
        return $this->context->render($view, $parameters, $response);
    }

    protected function form(
        string $view,
        string $type,
        object|array $data = null,
        callable|bool $persist = false,
        string|array|bool $action = null,
        array $parameters = null,
        array $options = null,
        Request $request = null,
        Response $response = null,
        string|bool $activity = null,
    ): Response {
        return $this->context->renderForm($view, $type, $data, $persist, $action, $parameters, $options, $request, $response, $activity);
    }

    protected static function subscribe(): array
    {
        return array();
    }

    public static function getSubscribedServices(): array
    {
        return self::subscribe() + parent::getSubscribedServices() + array(
            'account' => Account::class,
            'api' => ApiContext::class,
            'context' => ControllerContext::class,
        );
    }

    public function __get($name)
    {
        if (method_exists($this, $get = 'get' . $name)) {
            return $this->$get();
        }

        return $this->container->get($name);
    }
}