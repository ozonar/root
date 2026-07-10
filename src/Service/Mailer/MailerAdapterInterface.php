<?php

namespace App\Service\Mailer;

interface MailerAdapterInterface
{
    public function send(string $to, string $subject, string $body): bool;
}