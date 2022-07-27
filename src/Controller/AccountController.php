<?php

namespace App\Controller;

use App\Form\AccountType;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/account')]
class AccountController extends Controller
{
    #[Route('', 'account', methods: array('GET', 'POST'))]
    public function home()
    {
        return $this->form('account.home', AccountType::class, $this->getUser());
    }

    #[Route('/logout', 'logout', methods: 'POST')]
    public function logout()
    {
        throw new \RuntimeException('This is intended');
    }
}