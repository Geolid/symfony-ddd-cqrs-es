<?php

declare(strict_types=1);

namespace Crm\Customer\Application\IntegrationEvent\CustomerRegistered;

use Crm\Customer\Domain\Customer\Customer;
use Crm\Customer\Domain\Customer\Event\CustomerRegistered;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('crm.customer.publish_customer_registered')]
final readonly class CustomerRegisteredPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(CustomerRegistered::class)]
    public function __invoke(CustomerRegistered $event): void
    {
        $this->publisher->publish(Customer::class, $event->id->toString(), new CustomerRegisteredIntegrationEvent(
            customerId: $event->id->toString(),
            firstName: $event->firstName->value,
            lastName: $event->lastName->value,
            email: $event->email->value,
            registeredAt: $event->registeredAt,
        ));
    }
}
