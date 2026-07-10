<?php

namespace App\Security;

use App\Entity\User;
use App\Service\TokenService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class TokenAuthenticator extends AbstractAuthenticator
{
    private EntityManagerInterface $entityManager;
    private TokenService $tokenService;

    public function __construct(EntityManagerInterface $entityManager, TokenService $tokenService)
    {
        $this->entityManager = $entityManager;
        $this->tokenService = $tokenService;
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('X-Auth-Token');
    }

    public function authenticate(Request $request): Passport
    {
        $token = $request->headers->get('X-Auth-Token');

        if (null === $token) {
            throw new CustomUserMessageAuthenticationException('No API token provided');
        }

        $payload = $this->tokenService->validateToken($token);

        if (null === $payload || !isset($payload['user_id'])) {
            throw new CustomUserMessageAuthenticationException('Invalid or expired API token');
        }

        $user = $this->entityManager->getRepository(User::class)->find($payload['user_id']);

        if (null === $user) {
            throw new CustomUserMessageAuthenticationException('User not found');
        }

        return new SelfValidatingPassport(
            new UserBadge($user->getUserIdentifier(), function () use ($user) {
                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $data = [
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }
}