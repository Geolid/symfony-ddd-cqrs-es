<?php

declare(strict_types=1);

namespace Sales\Customer\Infrastructure\Persistence\EventStore\Translator;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Customer\Application\Event\CustomerBillingAddressSetIntegrationEvent;
use Sales\Customer\Application\Event\CustomerErasedIntegrationEvent;
use Sales\Customer\Application\Event\CustomerRegisteredIntegrationEvent;
use Sales\Customer\Application\Event\CustomerShippingAddressSetIntegrationEvent;
use Sales\Customer\Domain\Event\CustomerBillingAddressSet;
use Sales\Customer\Domain\Event\CustomerErased;
use Sales\Customer\Domain\Event\CustomerRegistered;
use Sales\Customer\Domain\Event\CustomerShippingAddressSet;
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

    #[Subscribe(CustomerShippingAddressSet::class)]
    public function onCustomerShippingAddressSet(CustomerShippingAddressSet $event): void
    {
        $this->append(
            IntegrationStreamId::build('sales.customer', $event->id),
            new CustomerShippingAddressSetIntegrationEvent(
                customerId: $event->id,
                firstName: $event->firstName,
                lastName: $event->lastName,
                street: $event->street,
                postalCode: $event->postalCode,
                city: $event->city,
                setAt: $event->setAt,
            ),
        );
    }

    #[Subscribe(CustomerBillingAddressSet::class)]
    public function onCustomerBillingAddressSet(CustomerBillingAddressSet $event): void
    {
        $this->append(
            IntegrationStreamId::build('sales.customer', $event->id),
            new CustomerBillingAddressSetIntegrationEvent(
                customerId: $event->id,
                firstName: $event->firstName,
                lastName: $event->lastName,
                street: $event->street,
                postalCode: $event->postalCode,
                city: $event->city,
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
