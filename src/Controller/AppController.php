<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AppController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

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

    #[Route('/register/invite/{token}', name: 'app_register_invite')]
    public function registerInvite(string $token): Response
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['verificationToken' => $token]);
        $inviteName = $user ? $user->getName() : null;

        return $this->render('register_invite.html.twig', [
            'inviteToken' => $token,
            'inviteName' => $inviteName,
        ]);
    }
}