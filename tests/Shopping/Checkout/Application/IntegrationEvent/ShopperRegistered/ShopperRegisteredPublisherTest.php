<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\IntegrationEvent\ShopperRegistered;

use PHPUnit\Framework\Attributes\Test;
use Shopping\Checkout\Application\IntegrationEvent\ShopperRegistered\ShopperRegisteredIntegrationEvent;
use Shopping\Tests\Checkout\Support\Builder\ShopperBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class ShopperRegisteredPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = ShopperBuilder::new();
        $shopper = $builder->create();

        // When
        $this->store($shopper);

        // Then
        $event = $this->publishedEventOf(ShopperRegisteredIntegrationEvent::class);
        self::assertSame($shopper->id->toString(), $event->shopperId);
        self::assertSame($builder['identityId'], $event->identityId);
        self::assertSame($builder['email']->value, $event->email);
        self::assertSame($builder['registeredAt']->format(\DateTimeInterface::ATOM), $event->registeredAt->format(\DateTimeInterface::ATOM));
    }
}
