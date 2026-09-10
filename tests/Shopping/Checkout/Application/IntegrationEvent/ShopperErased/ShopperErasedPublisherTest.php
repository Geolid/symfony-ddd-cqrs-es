<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\IntegrationEvent\ShopperErased;

use PHPUnit\Framework\Attributes\Test;
use Shopping\Checkout\Application\IntegrationEvent\ShopperErased\ShopperErasedIntegrationEvent;
use Shopping\Tests\Checkout\Support\Builder\ShopperBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class ShopperErasedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = ShopperBuilder::new()->erasureRequested()->erased();
        $shopper = $builder->create();

        // When
        $this->store($shopper);

        // Then
        $event = $this->publishedEventOf(ShopperErasedIntegrationEvent::class);
        self::assertSame($shopper->id->toString(), $event->shopperId);
        self::assertSame($builder['erasedAt']->format(\DateTimeInterface::ATOM), $event->erasedAt->format(\DateTimeInterface::ATOM));
    }
}
