<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\IntegrationEvent\IdentityConfirmed;

use Iam\Identity\Application\IntegrationEvent\IdentityConfirmed\IdentityConfirmedIntegrationEvent;
use Iam\Tests\Identity\Support\Factory\IdentityFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class IdentityConfirmedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $factory = IdentityFactory::new()->confirmed();
        $identity = $factory->create();

        // When
        $this->store($identity);

        // Then
        $event = $this->publishedEventOf(IdentityConfirmedIntegrationEvent::class);
        self::assertSame($identity->id->toString(), $event->identityId);
        self::assertSame(
            $identity->confirmedAt->format(\DateTimeInterface::ATOM),
            $event->confirmedAt->format(\DateTimeInterface::ATOM),
        );
    }
}
