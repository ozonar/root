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
        $userCount = $this->entityManager->getRepository(\App\Entity\User::class)->count([]);
        return $this->render('login.html.twig', [
            'canRegister' => $userCount === 0,
        ]);
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
        $invitedUser = $this->entityManager->getRepository(User::class)->findOneBy(['verificationToken' => $token]);

        if (!$invitedUser) {
            return $this->render('register_invite.html.twig', [
                'inviteToken' => $token,
                'inviteName' => null,
                'error' => 'Приглашение не найдено или срок его действия истёк.',
            ]);
        }

        if ($invitedUser->isVerified()) {
            return $this->render('register_invite.html.twig', [
                'inviteToken' => $token,
                'inviteName' => $invitedUser->getName(),
                'error' => 'Это приглашение уже было использовано.',
            ]);
        }

        // Find the project this user was invited to
        $userProject = $this->entityManager
            ->getRepository(\App\Entity\UserProject::class)
            ->findOneBy(['user' => $invitedUser]);

        $projectName = $userProject ? $userProject->getProject()->getName() : null;

        return $this->render('register_invite.html.twig', [
            'inviteToken' => $token,
            'inviteName' => $invitedUser->getName(),
            'projectName' => $projectName,
            'error' => null,
        ]);
    }
}