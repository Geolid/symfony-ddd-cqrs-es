<?php

declare(strict_types=1);

namespace Sales\Tests\Buyer\Application\IntegrationEvent\BuyerErasureRequested;

use PHPUnit\Framework\Attributes\Test;
use Sales\Buyer\Application\IntegrationEvent\BuyerErasureRequested\BuyerErasureRequestedIntegrationEvent;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class BuyerErasureRequestedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = BuyerBuilder::new()->erasureRequested();
        $buyer = $builder->create();

        // When
        $this->store($buyer);

        // Then
        $event = $this->publishedEventOf(BuyerErasureRequestedIntegrationEvent::class);
        self::assertSame($buyer->id->toString(), $event->buyerId);
        self::assertSame($builder['requestedAt']->format(\DateTimeInterface::ATOM), $event->requestedAt->format(\DateTimeInterface::ATOM));
    }
}
