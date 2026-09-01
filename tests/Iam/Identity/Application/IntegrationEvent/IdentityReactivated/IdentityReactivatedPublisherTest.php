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
        $factory = IdentityTestFactory::new()->suspended()->reactivated();
        $identity = $factory->create();

        // When
        $this->store($identity);

        // Then
        $event = $this->publishedEventOf(IdentityReactivatedIntegrationEvent::class);
        self::assertSame($identity->id->toString(), $event->identityId);
        self::assertSame(
            $factory['reactivatedAt']->format(\DateTimeImmutable::ATOM),
            $event->reactivatedAt->format(\DateTimeImmutable::ATOM),
        );
    }
}
