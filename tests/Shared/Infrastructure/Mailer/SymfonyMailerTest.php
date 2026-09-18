<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Mailer;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Application\Mailer\Exception\MailerException;
use Shared\Infrastructure\Mailer\SymfonyMailer;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface as SymfonyMailerInterface;

final class SymfonyMailerTest extends TestCase
{
    #[Test]
    public function itSends(): void
    {
        // Given
        $mailer = $this->createMock(SymfonyMailerInterface::class);
        $mailer->expects(self::once())->method('send');
        $symfonyMailer = new SymfonyMailer($mailer);

        // When
        $symfonyMailer->send('jane@example.com', 'Subject', 'Body');
    }

    #[Test]
    public function itFailsWhenTransportFails(): void
    {
        // Given
        $mailer = $this->createStub(SymfonyMailerInterface::class);
        $mailer->method('send')->willThrowException(new TransportException('Connection refused'));
        $symfonyMailer = new SymfonyMailer($mailer);

        // Then
        $this->expectException(MailerException::class);

        // When
        $symfonyMailer->send('jane@example.com', 'Subject', 'Body');
    }
}
