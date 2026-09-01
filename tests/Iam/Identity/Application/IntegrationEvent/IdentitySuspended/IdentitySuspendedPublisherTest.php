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
        $factory = IdentityTestFactory::new()->suspended();
        $identity = $factory->create();

        // When
        $this->store($identity);

        // Then
        $event = $this->publishedEventOf(IdentitySuspendedIntegrationEvent::class);
        self::assertSame($identity->id->toString(), $event->identityId);
        self::assertSame(
            $factory['suspendedAt']->format(\DateTimeImmutable::ATOM),
            $event->suspendedAt->format(\DateTimeImmutable::ATOM),
        );
    }
}
