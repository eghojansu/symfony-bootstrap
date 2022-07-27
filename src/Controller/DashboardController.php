<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;

#[Route('/dashboard')]
class DashboardController extends Controller
{
    #[Route('', 'dashboard', methods: 'GET')]
    public function home()
    {
        return $this->view('dashboard.home');
    }
}