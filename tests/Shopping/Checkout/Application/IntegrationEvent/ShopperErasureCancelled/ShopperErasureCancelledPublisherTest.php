<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\IntegrationEvent\ShopperErasureCancelled;

use PHPUnit\Framework\Attributes\Test;
use Shopping\Checkout\Application\IntegrationEvent\ShopperErasureCancelled\ShopperErasureCancelledIntegrationEvent;
use Shopping\Tests\Checkout\Support\Builder\ShopperBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class ShopperErasureCancelledPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = ShopperBuilder::new()->erasureRequested()->erasureCancelled();
        $shopper = $builder->create();

        // When
        $this->store($shopper);

        // Then
        $event = $this->publishedEventOf(ShopperErasureCancelledIntegrationEvent::class);
        self::assertSame($shopper->id->toString(), $event->shopperId);
        self::assertSame($builder['cancelledAt']->format(\DateTimeInterface::ATOM), $event->cancelledAt->format(\DateTimeInterface::ATOM));
    }
}
