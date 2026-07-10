<?php

namespace App\Service\Mailer;

use Psr\Log\LoggerInterface;

class MailerService
{
    private MailerAdapterInterface $adapter;
    private LoggerInterface $logger;

    public function __construct(MailerAdapterInterface $adapter, LoggerInterface $logger)
    {
        $this->adapter = $adapter;
        $this->logger = $logger;
    }

    public function send(string $to, string $subject, string $body): bool
    {
        $result = $this->adapter->send($to, $subject, $body);

        if (!$result) {
            $this->logger->error('Failed to send email', [
                'to' => $to,
                'subject' => $subject,
            ]);
        }

        return $result;
    }
}