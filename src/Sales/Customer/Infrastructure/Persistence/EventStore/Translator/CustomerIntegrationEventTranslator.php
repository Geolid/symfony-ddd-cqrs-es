<?php

declare(strict_types=1);

namespace Sales\Customer\Infrastructure\Persistence\EventStore\Translator;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Customer\Application\Event\CustomerBillingAddressRegisteredIntegrationEvent;
use Sales\Customer\Application\Event\CustomerErasedIntegrationEvent;
use Sales\Customer\Application\Event\CustomerRegisteredIntegrationEvent;
use Sales\Customer\Application\Event\CustomerShippingAddressRegisteredIntegrationEvent;
use Sales\Customer\Domain\Event\CustomerBillingAddressRegistered;
use Sales\Customer\Domain\Event\CustomerErased;
use Sales\Customer\Domain\Event\CustomerRegistered;
use Sales\Customer\Domain\Event\CustomerShippingAddressRegistered;
use Shared\Infrastructure\Persistence\EventStore\IntegrationStreamId;
use Shared\Infrastructure\Persistence\EventStore\Translator\AbstractIntegrationEventTranslator;
use Shared\Infrastructure\Persistence\EventStore\Translator\Translator;

#[Translator('sales.customer.integration')]
final readonly class CustomerIntegrationEventTranslator extends AbstractIntegrationEventTranslator
{
    #[Subscribe(CustomerRegistered::class)]
    public function onCustomerRegistered(CustomerRegistered $event): void
    {
        $this->append(
            IntegrationStreamId::build('sales.customer', $event->id),
            new CustomerRegisteredIntegrationEvent(
                customerId: $event->id,
                email: $event->email,
                registeredAt: $event->registeredAt,
            ),
        );
    }

    #[Subscribe(CustomerShippingAddressRegistered::class)]
    public function onCustomerShippingAddressRegistered(CustomerShippingAddressRegistered $event): void
    {
        $this->append(
            IntegrationStreamId::build('sales.customer', $event->id),
            new CustomerShippingAddressRegisteredIntegrationEvent(
                customerId: $event->id,
                address: $event->address,
                setAt: $event->setAt,
            ),
        );
    }

    #[Subscribe(CustomerBillingAddressRegistered::class)]
    public function onCustomerBillingAddressRegistered(CustomerBillingAddressRegistered $event): void
    {
        $this->append(
            IntegrationStreamId::build('sales.customer', $event->id),
            new CustomerBillingAddressRegisteredIntegrationEvent(
                customerId: $event->id,
                address: $event->address,
                setAt: $event->setAt,
            ),
        );
    }

    #[Subscribe(CustomerErased::class)]
    public function onCustomerErased(CustomerErased $event): void
    {
        $this->append(
            IntegrationStreamId::build('sales.customer', $event->id),
            new CustomerErasedIntegrationEvent(
                customerId: $event->id,
                erasedAt: $event->erasedAt,
            ),
        );
    }
}
