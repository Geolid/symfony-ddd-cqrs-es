<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Infrastructure\Persistence\EventStore\Translator;

use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Application\Event\CustomerErasedIntegrationEvent;
use Sales\Customer\Application\Event\CustomerRegisteredIntegrationEvent;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
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
        $published = $this->publishedTo(\sprintf('sales.customer.integration.%s', $customer->id()->toString()));
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
        $published = $this->publishedTo(\sprintf('sales.customer.integration.%s', $customer->id()->toString()));
        self::assertCount(2, $published);
        $event = $published[1];
        self::assertInstanceOf(CustomerErasedIntegrationEvent::class, $event);
        self::assertSame($customer->id()->toString(), $event->customerId);
    }
}
