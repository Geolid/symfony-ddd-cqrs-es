<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\IntegrationEvent\IdentitySuspended;

use Iam\Identity\Application\IntegrationEvent\IdentitySuspended\IdentitySuspendedIntegrationEvent;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class IdentitySuspendedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $suspendedAt = Clock::get()->now();
        $identity = IdentityTestFactory::new()->suspended(suspendedAt: $suspendedAt)->create();

        // When
        $this->store($identity);

        // Then
        $event = $this->publishedEventOf(IdentitySuspendedIntegrationEvent::class);
        self::assertSame($identity->id->toString(), $event->identityId);
        self::assertSame($suspendedAt->format(\DateTimeImmutable::ATOM), $event->suspendedAt->format(\DateTimeImmutable::ATOM));
    }
}
