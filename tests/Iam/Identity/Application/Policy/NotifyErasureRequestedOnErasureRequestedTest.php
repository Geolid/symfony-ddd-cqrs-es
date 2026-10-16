<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Policy;

use Compliance\Erasing\Application\IntegrationEvent\ErasureRequested\ErasureRequestedIntegrationEvent;
use Iam\Identity\Application\Policy\NotifyErasureRequestedOnErasureRequested;
use Iam\Tests\Identity\Support\Factory\IdentityFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Mime\Email;

final class NotifyErasureRequestedOnErasureRequestedTest extends AbstractIntegrationTestCase
{
    use MailerAssertionsTrait;

    #[Test]
    public function itNotifies(): void
    {
        // Given
        $identity = IdentityFactory::new()->create();
        $this->store($identity);
        $requestedAt = Clock::get()->now();
        $effectiveAt = $requestedAt->modify('+7 days');

        // When
        $this->trigger(NotifyErasureRequestedOnErasureRequested::class, new ErasureRequestedIntegrationEvent($identity->id->toString(), $requestedAt, $effectiveAt));

        // Then
        self::assertEmailCount(1);
        $message = self::getMailerMessage();
        self::assertInstanceOf(Email::class, $message);
        self::assertEmailAddressContains($message, 'To', $identity->email->value);
        self::assertEmailSubjectContains($message, 'Your account deletion request');
        self::assertEmailTextBodyContains($message, $effectiveAt->format('Y-m-d'));
    }
}
