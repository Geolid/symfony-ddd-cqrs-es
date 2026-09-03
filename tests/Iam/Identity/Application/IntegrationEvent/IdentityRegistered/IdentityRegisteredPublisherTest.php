<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\IntegrationEvent\IdentityRegistered;

use Iam\Identity\Application\IntegrationEvent\IdentityRegistered\IdentityRegisteredIntegrationEvent;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class IdentityRegisteredPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = IdentityBuilder::new();
        $identity = $builder->create();

        // When
        $this->store($identity);

        // Then
        $event = $this->publishedEventOf(IdentityRegisteredIntegrationEvent::class);
        self::assertSame($identity->id->toString(), $event->identityId);
        self::assertSame(
            $builder['registeredAt']->format(\DateTimeImmutable::ATOM),
            $event->registeredAt->format(\DateTimeImmutable::ATOM),
        );
    }
}
