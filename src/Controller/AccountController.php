<?php

namespace App\Controller;

use App\Entity\Csuser;
use App\Form\AccountType;
use App\Form\AccountPasswordType;
use App\Repository\CshistRepository;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/account')]
class AccountController extends Controller
{
    #[Route('', 'account', methods: array('GET', 'POST'))]
    public function home()
    {
        return $this->form(
            'account.home',
            AccountType::class,
            $this->account->fullUser(),
            activity: 'Update profile',
        );
    }

    #[Route('/password', 'password', methods: array('GET', 'POST'))]
    public function password(UserPasswordHasherInterface $hash)
    {
        return $this->form(
            'account.password',
            AccountPasswordType::class,
            $this->account->fullUser(),
            static fn (Csuser $user) =>  $user->setPassword($hash->hashPassword($user, $user->getNewPassword())),
            activity: 'Update password',
        );
    }

    #[Route('/activities', 'activities', methods: 'GET')]
    public function activities(CshistRepository $repo)
    {
        $items = $repo->getUserActivities($this->user);

        return $this->view('account.activities', compact('items'));
    }

    #[Route('/logout', 'logout', methods: 'POST')]
    public function logout()
    {
        throw new \RuntimeException('This is intended');
    }
}