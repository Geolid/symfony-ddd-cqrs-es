<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\IntegrationEvent\IdentityErased;

use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class IdentityErasedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = IdentityBuilder::new()->erased();
        $identity = $builder->create();

        // When
        $this->store($identity);

        // Then
        $event = $this->publishedEventOf(IdentityErasedIntegrationEvent::class);
        self::assertSame($identity->id->toString(), $event->identityId);
        self::assertSame(
            $builder['erasedAt']->format(\DateTimeInterface::ATOM),
            $event->erasedAt->format(\DateTimeInterface::ATOM),
        );
    }
}
