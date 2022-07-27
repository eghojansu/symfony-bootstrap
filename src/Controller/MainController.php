<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;

class MainController extends Controller
{
    #[Route('/', 'home', methods: 'GET')]
    public function home()
    {
        return $this->view('main.home');
    }

    #[Route('/about', 'about', methods: 'GET')]
    public function about()
    {
        return $this->view('main.about');
    }
}