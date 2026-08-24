<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\IntegrationEvent\IdentitySuspended;

use Iam\Identity\Application\IntegrationEvent\IdentitySuspended\IdentitySuspendedIntegrationEvent;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class IdentitySuspendedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
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
}
