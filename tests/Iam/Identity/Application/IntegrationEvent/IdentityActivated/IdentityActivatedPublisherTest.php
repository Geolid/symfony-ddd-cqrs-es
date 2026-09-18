<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\IntegrationEvent\IdentityActivated;

use Iam\Identity\Application\IntegrationEvent\IdentityActivated\IdentityActivatedIntegrationEvent;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class IdentityActivatedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = IdentityBuilder::new()->activated();
        $identity = $builder->create();

        // When
        $this->store($identity);

        // Then
        $event = $this->publishedEventOf(IdentityActivatedIntegrationEvent::class);
        self::assertSame($identity->id->toString(), $event->identityId);
        self::assertSame(
            $builder['activatedAt']->format(\DateTimeInterface::ATOM),
            $event->activatedAt->format(\DateTimeInterface::ATOM),
        );
    }
}
