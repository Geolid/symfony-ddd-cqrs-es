<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\IntegrationEvent\IdentityReactivated;

use Iam\Identity\Application\IntegrationEvent\IdentityReactivated\IdentityReactivatedIntegrationEvent;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class IdentityReactivatedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->suspended()->reactivated(reactivatedAt: new \DateTimeImmutable('2026-01-03T00:00:00+00:00'))->create();

        // When
        $this->store($identity);

        // Then
        $event = $this->publishedEventOf(IdentityReactivatedIntegrationEvent::class);
        self::assertSame($identity->id->toString(), $event->identityId);
        self::assertSame('2026-01-03T00:00:00+00:00', $event->reactivatedAt);
    }
}
