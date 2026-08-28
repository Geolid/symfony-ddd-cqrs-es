<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\IntegrationEvent\IdentityErased;

use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class IdentityErasedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $erasedAt = Clock::get()->now();
        $identity = IdentityTestFactory::new()->erased($erasedAt)->create();

        // When
        $this->store($identity);

        // Then
        $event = $this->publishedEventOf(IdentityErasedIntegrationEvent::class);
        self::assertSame($identity->id->toString(), $event->identityId);
        self::assertSame($erasedAt->format(\DateTimeImmutable::ATOM), $event->erasedAt->format(\DateTimeImmutable::ATOM));
    }
}
