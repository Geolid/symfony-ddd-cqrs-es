<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasing\Application\IntegrationEvent\ErasureRequested;

use Compliance\Erasing\Application\IntegrationEvent\ErasureRequested\ErasureRequestedIntegrationEvent;
use Compliance\Tests\Erasing\Support\Builder\ErasureBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class ErasureRequestedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = ErasureBuilder::new();
        $erasure = $builder->create();

        // When
        $this->store($erasure);

        // Then
        $event = $this->publishedEventOf(ErasureRequestedIntegrationEvent::class);
        self::assertSame($builder['identityId'], $event->identityId);
        self::assertSame(
            $builder['requestedAt']->format(\DateTimeInterface::ATOM),
            $event->requestedAt->format(\DateTimeInterface::ATOM),
        );
    }
}
