<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Application\IntegrationEvent\CustomerBillingAddressRegistered;

use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Application\IntegrationEvent\CustomerBillingAddressRegistered\CustomerBillingAddressRegisteredIntegrationEvent;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;
use Support\AbstractIntegrationTestCase;

final class CustomerBillingAddressRegisteredPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $customer = CustomerTestFactory::new()
            ->withBillingAddress(PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('8 avenue Foch', '75116', 'Paris', 'FR')))
            ->create();

        // When
        $this->store($customer);

        // Then
        $event = $this->publishedEventOf(CustomerBillingAddressRegisteredIntegrationEvent::class);
        self::assertSame($customer->id->toString(), $event->customerId);
        self::assertSame(
            ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'street' => '8 avenue Foch', 'postalCode' => '75116', 'city' => 'Paris', 'countryCode' => 'FR'],
            $event->address,
        );
    }
}
