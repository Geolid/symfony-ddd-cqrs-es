<?php

declare(strict_types=1);

namespace Crm\Customer\Application\IntegrationEvent\CustomerErasureCancelled;

use Crm\Customer\Domain\Customer\Customer;
use Crm\Customer\Domain\Customer\Event\CustomerErasureCancelled;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('crm.customer.publish_customer_erasure_cancelled')]
final readonly class CustomerErasureCancelledPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(CustomerErasureCancelled::class)]
    public function __invoke(CustomerErasureCancelled $event): void
    {
        $this->publisher->publish(Customer::class, $event->id->toString(), new CustomerErasureCancelledIntegrationEvent(
            customerId: $event->id->toString(),
            cancelledAt: $event->cancelledAt,
        ));
    }
}
