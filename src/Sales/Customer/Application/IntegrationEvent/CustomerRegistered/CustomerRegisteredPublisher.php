<?php

declare(strict_types=1);

namespace Sales\Customer\Application\IntegrationEvent\CustomerRegistered;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Customer\Domain\Customer;
use Sales\Customer\Domain\Event\CustomerRegistered;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('sales.customer.publish_customer_registered')]
final readonly class CustomerRegisteredPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(CustomerRegistered::class)]
    public function __invoke(CustomerRegistered $event): void
    {
        $this->publisher->publish(Customer::class, $event->id, new CustomerRegisteredIntegrationEvent(
            customerId: $event->id,
            email: $event->email,
            registeredAt: $event->registeredAt,
        ));
    }
}
