<?php

namespace App\Service\Mailer;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class UnisenderMailerAdapter implements MailerAdapterInterface
{
    private HttpClientInterface $httpClient;
    private string $apiKey;

    private const API_URL = 'https://api.unisender.com/ru/api/sendEmail';

    public function __construct(HttpClientInterface $httpClient, string $apiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
    }

    public function send(string $to, string $subject, string $body): bool
    {
        try {
            $response = $this->httpClient->request('POST', self::API_URL, [
                'body' => [
                    'api_key' => $this->apiKey,
                    'email' => $to,
                    'sender_name' => 'Checker App',
                    'sender_email' => 'noreply@checker.app',
                    'subject' => $subject,
                    'body' => $body,
                    'list_id' => null,
                ],
            ]);

            $data = $response->toArray();

            return isset($data['result']) && isset($data['result']['email_id']);
        } catch (\Exception $e) {
            return false;
        }
    }
}