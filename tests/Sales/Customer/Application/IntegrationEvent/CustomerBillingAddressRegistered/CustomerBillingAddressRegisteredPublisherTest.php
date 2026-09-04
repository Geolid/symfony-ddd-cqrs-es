<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Application\IntegrationEvent\CustomerBillingAddressRegistered;

use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Application\IntegrationEvent\CustomerBillingAddressRegistered\CustomerBillingAddressRegisteredIntegrationEvent;
use Sales\Tests\Customer\Support\Builder\CustomerBuilder;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Support\TestCase\AbstractIntegrationTestCase;

final class CustomerBillingAddressRegisteredPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $billingAddress = PostalAddress::of('Ada Lovelace', Address::of('8 avenue Foch', '75116', 'Paris', 'FR'));
        $customer = CustomerBuilder::new()
            ->billingAddressRegistered($billingAddress)
            ->create();

        // When
        $this->store($customer);

        // Then
        $event = $this->publishedEventOf(CustomerBillingAddressRegisteredIntegrationEvent::class);
        $address = $billingAddress->toArray();
        self::assertSame($customer->id->toString(), $event->customerId);
        self::assertSame($address, $event->address);
    }
}
