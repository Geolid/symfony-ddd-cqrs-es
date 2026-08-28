<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\IntegrationEvent\IdentityRegistered;

use Iam\Identity\Application\IntegrationEvent\IdentityRegistered\IdentityRegisteredIntegrationEvent;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class IdentityRegisteredPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $now = Clock::get()->now();
        $identity = IdentityTestFactory::new()->withRegisteredAt($now)->create();

        // When
        $this->store($identity);

        // Then
        $event = $this->publishedEventOf(IdentityRegisteredIntegrationEvent::class);
        self::assertSame($identity->id->toString(), $event->identityId);
        self::assertSame($now->format(\DateTimeImmutable::ATOM), $event->registeredAt->format(\DateTimeImmutable::ATOM));
    }
}
