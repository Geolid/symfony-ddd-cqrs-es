<?php

declare(strict_types=1);

namespace Sales\Customer\Application\IntegrationEvent\CustomerShippingAddressRegistered;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Customer\Domain\Customer;
use Sales\Customer\Domain\Event\CustomerShippingAddressRegistered;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('sales.customer.publish_customer_shipping_address_registered')]
final readonly class CustomerShippingAddressRegisteredPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(CustomerShippingAddressRegistered::class)]
    public function __invoke(CustomerShippingAddressRegistered $event): void
    {
        $this->publisher->publish(Customer::class, $event->id, new CustomerShippingAddressRegisteredIntegrationEvent(
            customerId: $event->id,
            address: $event->address,
            setAt: $event->setAt,
        ));
    }
}
