<?php

declare(strict_types=1);

namespace Shared\Application\Mailer;

use Shared\Application\Mailer\Exception\MailerException;

interface MailerInterface
{
    /**
     * @throws MailerException
     */
    public function send(string $to, string $subject, string $body): void;
}
