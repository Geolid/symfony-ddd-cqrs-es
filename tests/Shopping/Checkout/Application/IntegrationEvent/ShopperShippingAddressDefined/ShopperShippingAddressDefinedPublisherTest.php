<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\IntegrationEvent\ShopperShippingAddressDefined;

use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Mapper\PostalAddressMapper;
use Shopping\Checkout\Application\IntegrationEvent\ShopperShippingAddressDefined\ShopperShippingAddressDefinedIntegrationEvent;
use Shopping\Tests\Checkout\Support\Builder\ShopperBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class ShopperShippingAddressDefinedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = ShopperBuilder::new()->shippingAddressDefined();
        $shopper = $builder->create();

        // When
        $this->store($shopper);

        // Then
        $event = $this->publishedEventOf(ShopperShippingAddressDefinedIntegrationEvent::class);
        self::assertSame($shopper->id->toString(), $event->shopperId);
        self::assertSame($builder['identityId'], $event->identityId);
        self::assertSame(PostalAddressMapper::toArray($builder['shippingAddress']), $event->postalAddress);
        self::assertSame($builder['shippingAddressDefinedAt']->format(\DateTimeInterface::ATOM), $event->definedAt->format(\DateTimeInterface::ATOM));
    }
}
