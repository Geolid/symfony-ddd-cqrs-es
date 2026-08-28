<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\IntegrationEvent\IdentityReactivated;

use Iam\Identity\Application\IntegrationEvent\IdentityReactivated\IdentityReactivatedIntegrationEvent;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class IdentityReactivatedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $suspendedAt = Clock::get()->now();
        $reactivatedAt = $suspendedAt->modify('+1 day');
        $identity = IdentityTestFactory::new()->suspended(suspendedAt: $suspendedAt)->reactivated(reactivatedAt: $reactivatedAt)->create();

        // When
        $this->store($identity);

        // Then
        $event = $this->publishedEventOf(IdentityReactivatedIntegrationEvent::class);
        self::assertSame($identity->id->toString(), $event->identityId);
        self::assertSame($reactivatedAt->format(\DateTimeImmutable::ATOM), $event->reactivatedAt->format(\DateTimeImmutable::ATOM));
    }
}
