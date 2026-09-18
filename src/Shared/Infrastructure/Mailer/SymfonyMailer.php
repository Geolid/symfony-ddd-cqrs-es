<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Mailer;

use Shared\Application\Mailer\Exception\MailerException;
use Shared\Application\Mailer\MailerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface as SymfonyMailerInterface;
use Symfony\Component\Mime\Email;

final readonly class SymfonyMailer implements MailerInterface
{
    public function __construct(private SymfonyMailerInterface $mailer)
    {
    }

    /**
     * @throws MailerException
     */
    public function send(string $to, string $subject, string $body): void
    {
        try {
            $this->mailer->send(new Email()->to($to)->subject($subject)->text($body));
        } catch (TransportExceptionInterface $e) {
            throw MailerException::forRecipient($to, $e);
        }
    }
}
