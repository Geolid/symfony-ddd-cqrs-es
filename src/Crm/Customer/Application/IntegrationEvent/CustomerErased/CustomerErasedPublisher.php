<?php

declare(strict_types=1);

namespace Crm\Customer\Application\IntegrationEvent\CustomerErased;

use Crm\Customer\Domain\Customer\Customer;
use Crm\Customer\Domain\Customer\Event\CustomerErased;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Message\Message;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('crm.customer.publish_customer_erased')]
final readonly class CustomerErasedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(CustomerErased::class)]
    public function __invoke(CustomerErased $event, Message $message): void
    {
        $this->publisher->publish(new CustomerErasedIntegrationEvent(
            customerId: $event->id->toString(),
            erasedAt: $event->erasedAt,
        ), $message);
    }
}
