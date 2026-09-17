<?php

declare(strict_types=1);

namespace Shared\Application\Mailer;

interface MailerInterface
{
    public function send(string $to, string $subject, string $body): void;
}
