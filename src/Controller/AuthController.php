<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserProject;
use App\Service\TokenService;
use App\Service\StatusService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class AuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private TokenService $tokenService,
        private StatusService $statusService,
    ) {
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        // Only allow registration if no users exist
        $userCount = $this->entityManager->getRepository(User::class)->count([]);
        if ($userCount > 0) {
            return $this->json(['error' => 'Registration is closed. Use invite link.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            return $this->json(['error' => 'Email and password are required'], Response::HTTP_BAD_REQUEST);
        }

        $email = trim($data['email']);
        $password = $data['password'];
        $name = $data['name'] ?? null;
        $projectName = trim($data['projectName'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Invalid email format'], Response::HTTP_BAD_REQUEST);
        }

        if (strlen($password) < 6) {
            return $this->json(['error' => 'Password must be at least 6 characters'], Response::HTTP_BAD_REQUEST);
        }

        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            return $this->json(['error' => 'Email already registered'], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setName($name);
        $user->setRoles(['ROLE_USER']);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $verificationToken = $this->tokenService->generateToken();
        $user->setVerificationToken($verificationToken);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Create project if name provided
        if ($projectName !== '') {
            $project = new \App\Entity\Project();
            $project->setName($projectName);
            $project->setCreatedBy($user);
            $project->setOwner($user);

            $this->entityManager->persist($project);
            $this->entityManager->flush();

            // Add user as owner member
            $userProject = new \App\Entity\UserProject();
            $userProject->setUser($user);
            $userProject->setProject($project);
            $userProject->setRole('owner');

            $this->entityManager->persist($userProject);
            $this->entityManager->flush();

            // Create default statuses
            $this->statusService->createDefaultStatuses($project);

            // Set as current project
            $user->setCurrentProject($project);
            $this->entityManager->flush();
        }

        $authToken = $this->tokenService->generateAuthToken($user);

        return $this->json([
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'displayName' => $user->getDisplayName(),
            ],
            'token' => $authToken,
        ], Response::HTTP_CREATED);
    }

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            return $this->json(['error' => 'Email and password are required'], Response::HTTP_BAD_REQUEST);
        }

        $email = trim($data['email']);
        $password = $data['password'];

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $password)) {
            return $this->json(['error' => 'Неверные данные для входа'], Response::HTTP_UNAUTHORIZED);
        }

        $authToken = $this->tokenService->generateAuthToken($user);

        return $this->json([
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'displayName' => $user->getDisplayName(),
                'roles' => $user->getRoles(),
            ],
            'token' => $authToken,
        ]);
    }

    #[Route('/me', name: 'api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'displayName' => $user->getDisplayName(),
                'roles' => $user->getRoles(),
                'currentProjectId' => $user->getCurrentProject()?->getId(),
            ],
        ]);
    }

    #[Route('/me/name', name: 'api_me_name', methods: ['PUT'])]
    public function updateName(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['name'])) {
            return $this->json(['error' => 'Name is required'], Response::HTTP_BAD_REQUEST);
        }

        $name = trim($data['name']);

        if ($name === '') {
            return $this->json(['error' => 'Name cannot be empty'], Response::HTTP_BAD_REQUEST);
        }

        $user->setName($name);
        $this->entityManager->flush();

        return $this->json([
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'displayName' => $user->getDisplayName(),
            ],
        ]);
    }

    #[Route('/register/invite/{token}', name: 'api_register_invite', methods: ['POST'])]
    public function registerInvite(string $token, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            return $this->json(['error' => 'Email and password are required'], Response::HTTP_BAD_REQUEST);
        }

        $email = trim($data['email']);
        $password = $data['password'];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Invalid email format'], Response::HTTP_BAD_REQUEST);
        }

        if (strlen($password) < 6) {
            return $this->json(['error' => 'Password must be at least 6 characters'], Response::HTTP_BAD_REQUEST);
        }

        // Find user by invite token
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['verificationToken' => $token]);

        if (!$user) {
            return $this->json(['error' => 'Invalid or expired invite link'], Response::HTTP_NOT_FOUND);
        }

        // Check if user already completed registration
        if ($user->isVerified()) {
            return $this->json(['error' => 'Invite already used'], Response::HTTP_CONFLICT);
        }

        // Check if email already taken
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser && $existingUser->getId() !== $user->getId()) {
            return $this->json(['error' => 'Email already registered'], Response::HTTP_CONFLICT);
        }

        // Update user with real email and password
        $user->setEmail($email);
        $user->setName($data['name'] ?? $user->getName());

        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $user->setVerified(true);
        $user->setVerificationToken(null);

        $this->entityManager->flush();

        $authToken = $this->tokenService->generateAuthToken($user);

        return $this->json([
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'displayName' => $user->getDisplayName(),
            ],
            'token' => $authToken,
        ]);
    }

    #[Route('/invite/{token}/join', name: 'api_invite_join', methods: ['POST'])]
    public function joinByInvite(string $token): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        // Find the invited user by verification token
        $invitedUser = $this->entityManager->getRepository(User::class)->findOneBy(['verificationToken' => $token]);

        if (!$invitedUser) {
            return $this->json(['error' => 'Invalid or expired invite link'], Response::HTTP_NOT_FOUND);
        }

        // Find the project the invited user was added to
        $userProject = $this->entityManager
            ->getRepository(UserProject::class)
            ->findOneBy(['user' => $invitedUser]);

        if (!$userProject) {
            return $this->json(['error' => 'Invite project not found'], Response::HTTP_NOT_FOUND);
        }

        $project = $userProject->getProject();

        // Check if current user is already a member of this project
        $existingMembership = $this->entityManager
            ->getRepository(UserProject::class)
            ->findOneBy(['user' => $currentUser, 'project' => $project]);

        if ($existingMembership) {
            // Already a member — just switch to this project
            $currentUser->setCurrentProject($project);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'alreadyMember' => true,
                'project' => [
                    'id' => $project->getId(),
                    'name' => $project->getName(),
                ],
            ]);
        }

        // Add current user to the project as member
        $newUserProject = new UserProject();
        $newUserProject->setUser($currentUser);
        $newUserProject->setProject($project);
        $newUserProject->setRole('member');

        $this->entityManager->persist($newUserProject);

        // Set as current project
        $currentUser->setCurrentProject($project);

        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'alreadyMember' => false,
            'project' => [
                'id' => $project->getId(),
                'name' => $project->getName(),
            ],
        ]);
    }
}