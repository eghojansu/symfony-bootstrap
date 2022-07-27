<?php

namespace App\Service;

use App\Entity\Cshist;
use App\Entity\Csuser;
use App\Form\AccountType;
use App\Form\AccountPasswordType;
use App\Extension\ControllerContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class Account
{
    /** @var Csuser */
    private $user;

    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
        private TokenStorageInterface $tokenStorage,
        private RequestStack $requestStack,
    ) {}

    public function user(): Csuser|null
    {
        return $this->tokenStorage->getToken()?->getUser();
    }

    public function fullUser(): Csuser|null
    {
        return $this->user ?? (
            $this->user = ($user = $this->user()) ? $this->em->getRepository(Csuser::class)->find($user->getId()) : null
        );
    }

    public function record(
        string $activity,
        array $payload = null,
        bool $active = null,
        Csuser $user = null,
        Request $request = null,
    ): Cshist {
        $req = $request ?? $this->requestStack->getCurrentRequest();
        $info = sprintf('%s %s', $req->getMethod(), $req->getPathInfo());

        $history = new Cshist();
        $history->setName($activity);
        $history->setActive($active);
        $history->setPayload($payload);
        $history->setIp($req->getClientIp());
        $history->setAgent($req->headers->get('User-Agent'));
        $history->setRequest($info);
        $history->setUser($user ?? $this->fullUser());
        $history->setRecordDate(new \DateTime());

        $this->em->persist($history);
        $this->em->flush();

        return $history;
    }

    public function profileUpdate(): void
    {
        // $this->api->handleSave(AccountType::class, $this->context->user());
        // $this->record('update profile');
    }

    public function passwordUpdate(): void
    {
        // $this->api->handleSave(
        //     AccountPasswordType::class,
        //     $this->context->currentUser(),
        //     fn (Csuser $user) => $user->setPassword(
        //         $this->passwordHasher->hashPassword(
        //             $user,
        //             $user->getNewPassword(),
        //         ),
        //     ),
        // );
        // $this->record('update password');
    }
}