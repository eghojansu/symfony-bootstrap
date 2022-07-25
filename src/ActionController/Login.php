<?php

namespace App\ActionController;

use App\Extension\ApiContext;
use App\Extension\Controller\ActionController;
use App\Extension\ControllerContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class Login extends ActionController
{
    public function __invoke(
        ControllerContext $context,
        Request $request,
        AuthenticationUtils $auth,
        ApiContext $api,
    ) {
        if ($request->isMethod('POST')) {
            return $api->message(
                $auth->getLastAuthenticationError(),
                null,
                false,
            );
        }

        return $context->render('main/login.html.twig');
    }

    protected static function subscribe(): array
    {
        return array(
            'authUtils' => AuthenticationUtils::class,
            'api' => ApiContext::class,
        );
    }
}