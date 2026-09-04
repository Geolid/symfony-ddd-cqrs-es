<?php

declare(strict_types=1);

namespace Sales\Tests\Buyer\Application\IntegrationEvent\BuyerShippingAddressRegistered;

use PHPUnit\Framework\Attributes\Test;
use Sales\Buyer\Application\IntegrationEvent\BuyerShippingAddressRegistered\BuyerShippingAddressRegisteredIntegrationEvent;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Support\TestCase\AbstractIntegrationTestCase;

final class BuyerShippingAddressRegisteredPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $shippingAddress = PostalAddress::of('Ada Lovelace', Address::of('12 rue des Lilas', '75001', 'Paris', 'FR'));
        $buyer = BuyerBuilder::new()
            ->shippingAddressRegistered($shippingAddress)
            ->create();

        // When
        $this->store($buyer);

        // Then
        $event = $this->publishedEventOf(BuyerShippingAddressRegisteredIntegrationEvent::class);
        $address = $shippingAddress->toArray();
        self::assertSame($buyer->id->toString(), $event->buyerId);
        self::assertSame($address, $event->address);
    }
}
