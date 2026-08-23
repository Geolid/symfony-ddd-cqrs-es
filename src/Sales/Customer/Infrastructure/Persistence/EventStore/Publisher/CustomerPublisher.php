<?php

declare(strict_types=1);

namespace Sales\Customer\Infrastructure\Persistence\EventStore\Publisher;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Customer\Application\Event\CustomerBillingAddressRegisteredIntegrationEvent;
use Sales\Customer\Application\Event\CustomerErasedIntegrationEvent;
use Sales\Customer\Application\Event\CustomerRegisteredIntegrationEvent;
use Sales\Customer\Application\Event\CustomerShippingAddressRegisteredIntegrationEvent;
use Sales\Customer\Domain\Customer;
use Sales\Customer\Domain\Event\CustomerBillingAddressRegistered;
use Sales\Customer\Domain\Event\CustomerErased;
use Sales\Customer\Domain\Event\CustomerRegistered;
use Sales\Customer\Domain\Event\CustomerShippingAddressRegistered;
use Shared\Infrastructure\Persistence\EventStore\Publisher\IntegrationEventAppenderInterface;
use Shared\Infrastructure\Persistence\EventStore\Publisher\Publisher;

#[Publisher('sales.customer.integration')]
final readonly class CustomerPublisher
{
    public function __construct(private IntegrationEventAppenderInterface $appender)
    {
    }

    #[Subscribe(CustomerRegistered::class)]
    public function onCustomerRegistered(CustomerRegistered $event): void
    {
        $this->appender->append(Customer::class, $event->id, new CustomerRegisteredIntegrationEvent(
            customerId: $event->id,
            email: $event->email,
            registeredAt: $event->registeredAt,
        ));
    }

    #[Subscribe(CustomerShippingAddressRegistered::class)]
    public function onCustomerShippingAddressRegistered(CustomerShippingAddressRegistered $event): void
    {
        $this->appender->append(Customer::class, $event->id, new CustomerShippingAddressRegisteredIntegrationEvent(
            customerId: $event->id,
            address: $event->address,
            setAt: $event->setAt,
        ));
    }

    #[Subscribe(CustomerBillingAddressRegistered::class)]
    public function onCustomerBillingAddressRegistered(CustomerBillingAddressRegistered $event): void
    {
        $this->appender->append(Customer::class, $event->id, new CustomerBillingAddressRegisteredIntegrationEvent(
            customerId: $event->id,
            address: $event->address,
            setAt: $event->setAt,
        ));
    }

    #[Subscribe(CustomerErased::class)]
    public function onCustomerErased(CustomerErased $event): void
    {
        $this->appender->append(Customer::class, $event->id, new CustomerErasedIntegrationEvent(
            customerId: $event->id,
            erasedAt: $event->erasedAt,
        ));
    }
}
