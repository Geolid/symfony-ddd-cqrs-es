<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\IntegrationEvent\ShopperBillingAddressDefined;

use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Mapper\PostalAddressMapper;
use Shopping\Checkout\Application\IntegrationEvent\ShopperBillingAddressDefined\ShopperBillingAddressDefinedIntegrationEvent;
use Shopping\Tests\Checkout\Support\Builder\ShopperBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class ShopperBillingAddressDefinedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = ShopperBuilder::new()->billingAddressDefined();
        $shopper = $builder->create();

        // When
        $this->store($shopper);

        // Then
        $event = $this->publishedEventOf(ShopperBillingAddressDefinedIntegrationEvent::class);
        self::assertSame($shopper->id->toString(), $event->shopperId);
        self::assertSame($builder['identityId'], $event->identityId);
        self::assertSame(PostalAddressMapper::toArray($builder['billingAddress']), $event->postalAddress);
        self::assertSame($builder['billingAddressDefinedAt']->format(\DateTimeInterface::ATOM), $event->definedAt->format(\DateTimeInterface::ATOM));
    }
}
