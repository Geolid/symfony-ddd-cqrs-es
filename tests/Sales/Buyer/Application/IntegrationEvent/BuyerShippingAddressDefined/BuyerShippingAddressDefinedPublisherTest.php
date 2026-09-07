<?php

declare(strict_types=1);

namespace Sales\Tests\Buyer\Application\IntegrationEvent\BuyerShippingAddressDefined;

use PHPUnit\Framework\Attributes\Test;
use Sales\Buyer\Application\IntegrationEvent\BuyerShippingAddressDefined\BuyerShippingAddressDefinedIntegrationEvent;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class BuyerShippingAddressDefinedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = BuyerBuilder::new()->shippingAddressDefined();
        $buyer = $builder->create();

        // When
        $this->store($buyer);

        // Then
        $event = $this->publishedEventOf(BuyerShippingAddressDefinedIntegrationEvent::class);
        self::assertSame($buyer->id->toString(), $event->buyerId);
        self::assertSame($builder['identityId'], $event->identityId);
        self::assertSame($builder['shippingAddress']->toArray(), $event->postalAddress);
        self::assertSame($builder['shippingAddressDefinedAt']->format(\DateTimeInterface::ATOM), $event->definedAt->format(\DateTimeInterface::ATOM));
    }
}
