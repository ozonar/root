<?php

namespace App\Service;

use App\Entity\User;

class TokenService
{
    private int $tokenLifetime = 365 * 24 * 3600; // 1 year in seconds

    public function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function generateAuthToken(User $user): string
    {
        $payload = [
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
            'exp' => time() + $this->tokenLifetime,
            'iat' => time(),
        ];

        $token = $this->encode($payload);

        return $token;
    }

    public function validateToken(string $token): ?array
    {
        try {
            $payload = $this->decode($token);
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return null;
            }
            return $payload;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function encode(array $payload): string
    {
        $json = json_encode($payload);
        return base64_encode($json) . '.' . hash_hmac('sha256', $json, $_SERVER['APP_SECRET'] ?? 'default_secret');
    }

    private function decode(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return null;
        }

        $json = base64_decode($parts[0]);
        $hash = hash_hmac('sha256', $json, $_SERVER['APP_SECRET'] ?? 'default_secret');

        if (!hash_equals($hash, $parts[1])) {
            return null;
        }

        return json_decode($json, true);
    }
}