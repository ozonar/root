<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AppController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(): Response
    {
        return $this->render('login.html.twig');
    }

    #[Route('/', name: 'app_index')]
    public function index(): Response
    {
        return $this->render('index.html.twig');
    }

    #[Route('/page/{id}', name: 'app_page', requirements: ['id' => '\d+'])]
    public function page(int $id): Response
    {
        return $this->render('page.html.twig', [
            'pageId' => $id,
        ]);
    }
}