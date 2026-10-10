<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\IntegrationEvent\IdentityRegistered;

use Iam\Identity\Application\IntegrationEvent\IdentityRegistered\IdentityRegisteredIntegrationEvent;
use Iam\Tests\Identity\Support\Factory\IdentityFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class IdentityRegisteredPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $factory = IdentityFactory::new();
        $identity = $factory->create();

        // When
        $this->store($identity);

        // Then
        $event = $this->publishedEventOf(IdentityRegisteredIntegrationEvent::class);
        self::assertSame($identity->id->toString(), $event->identityId);
        self::assertSame($identity->fullName->value, $event->fullName);
        self::assertSame($identity->email->value, $event->email);
        self::assertSameDate($identity->registeredAt, $event->registeredAt);
    }
}
