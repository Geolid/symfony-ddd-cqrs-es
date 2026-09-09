<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasing\Application\IntegrationEvent\ErasureCancelled;

use Compliance\Erasing\Application\IntegrationEvent\ErasureCancelled\ErasureCancelledIntegrationEvent;
use Compliance\Tests\Erasing\Support\Builder\ErasureBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class ErasureCancelledPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = ErasureBuilder::new()->cancelled();
        $erasure = $builder->create();

        // When
        $this->store($erasure);

        // Then
        $event = $this->publishedEventOf(ErasureCancelledIntegrationEvent::class);
        self::assertSame($builder['identityId'], $event->identityId);
        self::assertSame(
            $builder['cancelledAt']->format(\DateTimeInterface::ATOM),
            $event->cancelledAt->format(\DateTimeInterface::ATOM),
        );
    }
}
