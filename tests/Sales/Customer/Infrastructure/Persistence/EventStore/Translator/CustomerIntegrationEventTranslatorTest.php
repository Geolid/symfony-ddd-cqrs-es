<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Infrastructure\Persistence\EventStore\Translator;

use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Application\Event\CustomerBillingAddressSetIntegrationEvent;
use Sales\Customer\Application\Event\CustomerErasedIntegrationEvent;
use Sales\Customer\Application\Event\CustomerRegisteredIntegrationEvent;
use Sales\Customer\Application\Event\CustomerShippingAddressSetIntegrationEvent;
use Sales\Customer\Domain\Repository\CustomerAddressesRepositoryInterface;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;
use Shared\Infrastructure\Persistence\EventStore\IntegrationStreamId;
use Support\AbstractIntegrationTestCase;

final class CustomerIntegrationEventTranslatorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishesTheRegistrationOnCustomerRegistered(): void
    {
        // Given
        $customer = CustomerTestFactory::new()->withEmail('buyer@example.com')->create();

        // When
        $this->store($customer);

        // Then
        $published = $this->publishedTo(IntegrationStreamId::build('sales.customer', $customer->id()->toString()));
        self::assertCount(1, $published);
        $event = $published[0];
        self::assertInstanceOf(CustomerRegisteredIntegrationEvent::class, $event);
        self::assertSame($customer->id()->toString(), $event->customerId);
        self::assertSame('buyer@example.com', $event->email);
    }

    #[Test]
    public function itPublishesTheErasureOnCustomerErased(): void
    {
        // Given
        $customer = CustomerTestFactory::new()->erased()->create();

        // When
        $this->store($customer);

        // Then
        $published = $this->publishedTo(IntegrationStreamId::build('sales.customer', $customer->id()->toString()));
        self::assertCount(2, $published);
        $event = $published[1];
        self::assertInstanceOf(CustomerErasedIntegrationEvent::class, $event);
        self::assertSame($customer->id()->toString(), $event->customerId);
    }

    #[Test]
    public function itPublishesTheShippingAddressOnCustomerShippingAddressSet(): void
    {
        // Given
        $customer = CustomerTestFactory::new()->store();
        $customerAddresses = $this->service(CustomerAddressesRepositoryInterface::class)->load($customer->id());
        $customerAddresses->setShippingAddress(
            PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('12 rue des Lilas', '75001', 'Paris')),
            new \DateTimeImmutable('now +00:00'),
        );

        // When
        $this->store($customerAddresses);

        // Then
        $published = $this->publishedTo(IntegrationStreamId::build('sales.customer', $customer->id()->toString()));
        self::assertCount(2, $published);
        $event = $published[1];
        self::assertInstanceOf(CustomerShippingAddressSetIntegrationEvent::class, $event);
        self::assertSame($customer->id()->toString(), $event->customerId);
        self::assertSame('12 rue des Lilas', $event->address['street']);
    }

    #[Test]
    public function itPublishesTheBillingAddressOnCustomerBillingAddressSet(): void
    {
        // Given
        $customer = CustomerTestFactory::new()->store();
        $customerAddresses = $this->service(CustomerAddressesRepositoryInterface::class)->load($customer->id());
        $customerAddresses->setBillingAddress(
            PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('8 avenue Foch', '75116', 'Paris')),
            new \DateTimeImmutable('now +00:00'),
        );

        // When
        $this->store($customerAddresses);

        // Then
        $published = $this->publishedTo(IntegrationStreamId::build('sales.customer', $customer->id()->toString()));
        self::assertCount(2, $published);
        $event = $published[1];
        self::assertInstanceOf(CustomerBillingAddressSetIntegrationEvent::class, $event);
        self::assertSame($customer->id()->toString(), $event->customerId);
        self::assertSame('8 avenue Foch', $event->address['street']);
    }
}
