<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasing\Application\IntegrationEvent\ErasureRequested;

use Compliance\Erasing\Application\IntegrationEvent\ErasureRequested\ErasureRequestedIntegrationEvent;
use Compliance\Tests\Erasing\Support\Factory\ErasureFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class ErasureRequestedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $erasure = ErasureFactory::new()->create();

        // When
        $this->store($erasure);

        // Then
        $event = $this->publishedEventOf(ErasureRequestedIntegrationEvent::class);
        self::assertSame($erasure->identityId, $event->identityId);
        self::assertSameDate($erasure->requestedAt, $event->requestedAt);
    }
}
