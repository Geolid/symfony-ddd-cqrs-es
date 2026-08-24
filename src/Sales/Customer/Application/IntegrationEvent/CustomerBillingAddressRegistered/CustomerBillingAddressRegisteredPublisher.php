<?php

declare(strict_types=1);

namespace Sales\Customer\Application\IntegrationEvent\CustomerBillingAddressRegistered;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Customer\Domain\Customer;
use Sales\Customer\Domain\Event\CustomerBillingAddressRegistered;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('sales.customer.customer_billing_address_registered_publisher')]
final readonly class CustomerBillingAddressRegisteredPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(CustomerBillingAddressRegistered::class)]
    public function __invoke(CustomerBillingAddressRegistered $event): void
    {
        $this->publisher->publish(Customer::class, $event->id, new CustomerBillingAddressRegisteredIntegrationEvent(
            customerId: $event->id,
            address: $event->address,
            setAt: $event->setAt,
        ));
    }
}
