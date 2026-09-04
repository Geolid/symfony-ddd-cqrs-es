<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Application\IntegrationEvent\CustomerShippingAddressRegistered;

use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Application\IntegrationEvent\CustomerShippingAddressRegistered\CustomerShippingAddressRegisteredIntegrationEvent;
use Sales\Tests\Customer\Support\Builder\CustomerBuilder;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Support\TestCase\AbstractIntegrationTestCase;

final class CustomerShippingAddressRegisteredPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $shippingAddress = PostalAddress::of('Ada Lovelace', Address::of('12 rue des Lilas', '75001', 'Paris', 'FR'));
        $customer = CustomerBuilder::new()
            ->shippingAddressRegistered($shippingAddress)
            ->create();

        // When
        $this->store($customer);

        // Then
        $event = $this->publishedEventOf(CustomerShippingAddressRegisteredIntegrationEvent::class);
        $address = $shippingAddress->toArray();
        self::assertSame($customer->id->toString(), $event->customerId);
        self::assertSame($address, $event->address);
    }
}
