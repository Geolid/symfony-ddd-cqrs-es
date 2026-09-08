<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasing\Application\IntegrationEvent\ErasureApproved;

use Compliance\Erasing\Application\IntegrationEvent\ErasureApproved\ErasureApprovedIntegrationEvent;
use Compliance\Tests\Erasing\Support\Builder\ErasureBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class ErasureApprovedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = ErasureBuilder::new()->approved();
        $erasure = $builder->create();

        // When
        $this->store($erasure);

        // Then
        $event = $this->publishedEventOf(ErasureApprovedIntegrationEvent::class);
        self::assertSame($builder['identityId'], $event->identityId);
        self::assertSame(
            $builder['approvedAt']->format(\DateTimeInterface::ATOM),
            $event->approvedAt->format(\DateTimeInterface::ATOM),
        );
    }
}
