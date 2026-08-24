<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\IntegrationEvent\IdentityErased;

use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class IdentityErasedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
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
}
