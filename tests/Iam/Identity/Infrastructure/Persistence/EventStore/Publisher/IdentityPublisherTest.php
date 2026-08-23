<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Persistence\EventStore\Publisher;

use Iam\Identity\Application\Event\IdentityErasedIntegrationEvent;
use Iam\Identity\Application\Event\IdentityReactivatedIntegrationEvent;
use Iam\Identity\Application\Event\IdentityRegisteredIntegrationEvent;
use Iam\Identity\Application\Event\IdentitySuspendedIntegrationEvent;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class IdentityPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishesOnIdentityRegistered(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->withRegisteredAt(new \DateTimeImmutable('2026-01-01T00:00:00+00:00'))->create();

        // When
        $this->store($identity);

        // Then
        $event = $this->publishedEventOfType(IdentityRegisteredIntegrationEvent::class);
        self::assertSame($identity->id->toString(), $event->identityId);
        self::assertSame('2026-01-01T00:00:00+00:00', $event->registeredAt);
    }

    #[Test]
    public function itPublishesOnIdentityErased(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->erased(new \DateTimeImmutable('2026-01-02T00:00:00+00:00'))->create();

        // When
        $this->store($identity);

        // Then
        $event = $this->publishedEventOfType(IdentityErasedIntegrationEvent::class);
        self::assertSame($identity->id->toString(), $event->identityId);
        self::assertSame('2026-01-02T00:00:00+00:00', $event->erasedAt);
    }

    #[Test]
    public function itPublishesOnIdentitySuspended(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->suspended(suspendedAt: new \DateTimeImmutable('2026-01-02T00:00:00+00:00'))->create();

        // When
        $this->store($identity);

        // Then
        $event = $this->publishedEventOfType(IdentitySuspendedIntegrationEvent::class);
        self::assertSame($identity->id->toString(), $event->identityId);
        self::assertSame('2026-01-02T00:00:00+00:00', $event->suspendedAt);
    }

    #[Test]
    public function itPublishesOnIdentityReactivated(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->suspended()->reactivated(reactivatedAt: new \DateTimeImmutable('2026-01-03T00:00:00+00:00'))->create();

        // When
        $this->store($identity);

        // Then
        $event = $this->publishedEventOfType(IdentityReactivatedIntegrationEvent::class);
        self::assertSame($identity->id->toString(), $event->identityId);
        self::assertSame('2026-01-03T00:00:00+00:00', $event->reactivatedAt);
    }
}
