<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasing\Application\IntegrationEvent\ErasureCancelled;

use Compliance\Erasing\Application\IntegrationEvent\ErasureCancelled\ErasureCancelledIntegrationEvent;
use Compliance\Tests\Erasing\Support\Factory\ErasureFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class ErasureCancelledPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $erasure = ErasureFactory::new()->cancelled()->create();

        // When
        $this->store($erasure);

        // Then
        $event = $this->publishedEventOf(ErasureCancelledIntegrationEvent::class);
        self::assertSame($erasure->identityId, $event->identityId);
        self::assertSameDate($erasure->cancelledAt, $event->cancelledAt);
    }
}
