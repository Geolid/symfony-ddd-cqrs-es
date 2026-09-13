<?php

declare(strict_types=1);

namespace Crm\Customer\Application\IntegrationEvent\CustomerErasureRequested;

use Crm\Customer\Domain\Customer\Customer;
use Crm\Customer\Domain\Customer\Event\CustomerErasureRequested;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('crm.customer.publish_customer_erasure_requested')]
final readonly class CustomerErasureRequestedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(CustomerErasureRequested::class)]
    public function __invoke(CustomerErasureRequested $event): void
    {
        $this->publisher->publish(Customer::class, $event->id->toString(), new CustomerErasureRequestedIntegrationEvent(
            customerId: $event->id->toString(),
            requestedAt: $event->requestedAt,
        ));
    }
}
