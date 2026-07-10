<?php

namespace App\Service\Mailer;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class DirectMailerAdapter implements MailerAdapterInterface
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function send(string $to, string $subject, string $body): bool
    {
        try {
            $email = (new Email())
                ->to($to)
                ->subject($subject)
                ->html($body);

            $this->mailer->send($email);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}