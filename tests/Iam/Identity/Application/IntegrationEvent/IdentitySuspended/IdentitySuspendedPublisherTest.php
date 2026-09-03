<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\IntegrationEvent\IdentitySuspended;

use Iam\Identity\Application\IntegrationEvent\IdentitySuspended\IdentitySuspendedIntegrationEvent;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class IdentitySuspendedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = IdentityBuilder::new()->suspended();
        $identity = $builder->create();

        // When
        $this->store($identity);

        // Then
        $event = $this->publishedEventOf(IdentitySuspendedIntegrationEvent::class);
        self::assertSame($identity->id->toString(), $event->identityId);
        self::assertSame(
            $builder['suspendedAt']->format(\DateTimeInterface::ATOM),
            $event->suspendedAt->format(\DateTimeInterface::ATOM),
        );
    }
}
