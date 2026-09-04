<?php

declare(strict_types=1);

namespace Sales\Tests\Buyer\Application\IntegrationEvent\BuyerBillingAddressRegistered;

use PHPUnit\Framework\Attributes\Test;
use Sales\Buyer\Application\IntegrationEvent\BuyerBillingAddressRegistered\BuyerBillingAddressRegisteredIntegrationEvent;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Support\TestCase\AbstractIntegrationTestCase;

final class BuyerBillingAddressRegisteredPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $billingAddress = PostalAddress::of('Ada Lovelace', Address::of('8 avenue Foch', '75116', 'Paris', 'FR'));
        $buyer = BuyerBuilder::new()
            ->billingAddressRegistered($billingAddress)
            ->create();

        // When
        $this->store($buyer);

        // Then
        $event = $this->publishedEventOf(BuyerBillingAddressRegisteredIntegrationEvent::class);
        $address = $billingAddress->toArray();
        self::assertSame($buyer->id->toString(), $event->buyerId);
        self::assertSame($address, $event->address);
    }
}
