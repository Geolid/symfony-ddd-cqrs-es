<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Infrastructure\Persistence\EventStore\Translator;

use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Application\Event\CustomerBillingAddressRegisteredIntegrationEvent;
use Sales\Customer\Application\Event\CustomerErasedIntegrationEvent;
use Sales\Customer\Application\Event\CustomerRegisteredIntegrationEvent;
use Sales\Customer\Application\Event\CustomerShippingAddressRegisteredIntegrationEvent;
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
    public function itPublishesTheShippingAddressOnCustomerShippingAddressRegistered(): void
    {
        // Given
        $customer = CustomerTestFactory::new()
            ->withShippingAddress(PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('12 rue des Lilas', '75001', 'Paris')))
            ->create();

        // When
        $this->store($customer);

        // Then
        $published = $this->publishedTo(IntegrationStreamId::build('sales.customer', $customer->id()->toString()));
        self::assertCount(2, $published);
        $event = $published[1];
        self::assertInstanceOf(CustomerShippingAddressRegisteredIntegrationEvent::class, $event);
        self::assertSame($customer->id()->toString(), $event->customerId);
        self::assertSame(
            ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'street' => '12 rue des Lilas', 'postalCode' => '75001', 'city' => 'Paris'],
            $event->address,
        );
    }

    #[Test]
    public function itPublishesTheBillingAddressOnCustomerBillingAddressRegistered(): void
    {
        // Given
        $customer = CustomerTestFactory::new()
            ->withBillingAddress(PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('8 avenue Foch', '75116', 'Paris')))
            ->create();

        // When
        $this->store($customer);

        // Then
        $published = $this->publishedTo(IntegrationStreamId::build('sales.customer', $customer->id()->toString()));
        self::assertCount(2, $published);
        $event = $published[1];
        self::assertInstanceOf(CustomerBillingAddressRegisteredIntegrationEvent::class, $event);
        self::assertSame($customer->id()->toString(), $event->customerId);
        self::assertSame(
            ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'street' => '8 avenue Foch', 'postalCode' => '75116', 'city' => 'Paris'],
            $event->address,
        );
    }
}
