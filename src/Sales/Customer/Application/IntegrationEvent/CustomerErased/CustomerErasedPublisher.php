<?php

declare(strict_types=1);

namespace Sales\Customer\Application\IntegrationEvent\CustomerErased;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Customer\Domain\Customer;
use Sales\Customer\Domain\Event\CustomerErased;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('sales.customer.customer_erased_publisher')]
final readonly class CustomerErasedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(CustomerErased::class)]
    public function __invoke(CustomerErased $event): void
    {
        $this->publisher->publish(Customer::class, $event->id, new CustomerErasedIntegrationEvent(
            customerId: $event->id,
            erasedAt: $event->erasedAt,
        ));
    }
}
