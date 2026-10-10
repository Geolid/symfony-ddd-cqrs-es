<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\IntegrationEvent\IdentityReactivated;

use Iam\Identity\Application\IntegrationEvent\IdentityReactivated\IdentityReactivatedIntegrationEvent;
use Iam\Tests\Identity\Support\Factory\IdentityFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class IdentityReactivatedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $factory = IdentityFactory::new()->confirmed()->suspended()->reactivated();
        $identity = $factory->create();

        // When
        $this->store($identity);

        // Then
        $event = $this->publishedEventOf(IdentityReactivatedIntegrationEvent::class);
        self::assertSame($identity->id->toString(), $event->identityId);
        self::assertSameDate($identity->reactivatedAt, $event->reactivatedAt);
    }
}
