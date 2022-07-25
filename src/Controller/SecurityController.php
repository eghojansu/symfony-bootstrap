<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends Controller
{
    #[Route('/login', 'login', methods: array('GET', 'POST'))]
    public function login(AuthenticationUtils $utils)
    {
        if ($this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('home');
        }

        return $this->render('main/login.html.twig', array(
            'error' => $utils->getLastAuthenticationError(),
            'username' => $utils->getLastUsername(),
        ));
    }
}