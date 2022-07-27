<?php

namespace App\EventSubscriber;

use App\Extension\ApiContext;
use App\Service\Account;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class AuthSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Account $account,
        private ApiContext $api,
    ) {}

    public function onLogoutEvent(LogoutEvent $event): void
    {
        if ('json' === $event->getRequest()->getPreferredFormat()) {
            $event->setResponse($this->api->done('You have been logged out', true));
        }

        $this->account->record('logout');
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $this->account->record('login', null, null, $event->getUser());
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => 'onLogoutEvent',
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }
}
