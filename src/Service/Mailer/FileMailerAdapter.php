<?php

namespace App\Service\Mailer;

class FileMailerAdapter implements MailerAdapterInterface
{
    private string $logDir;

    public function __construct(string $logDir)
    {
        $this->logDir = $logDir;
    }

    public function send(string $to, string $subject, string $body): bool
    {
        try {
            $filename = $this->logDir . '/mail_' . date('Y-m-d_H-i-s') . '_' . uniqid() . '.eml';

            $content = sprintf(
                "To: %s\nSubject: %s\nDate: %s\nContent-Type: text/html; charset=UTF-8\n\n%s",
                $to,
                $subject,
                date('r'),
                $body
            );

            file_put_contents($filename, $content);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}