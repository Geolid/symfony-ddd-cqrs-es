<?php

declare(strict_types=1);

namespace Crm\Customer\Application\IntegrationEvent\CustomerRegistered;

use Crm\Customer\Domain\Customer\Customer;
use Crm\Customer\Domain\Customer\Event\CustomerRegistered;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Message\Message;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('crm.customer.publish_customer_registered')]
final readonly class CustomerRegisteredPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(CustomerRegistered::class)]
    public function __invoke(CustomerRegistered $event, Message $message): void
    {
        $this->publisher->publish(new CustomerRegisteredIntegrationEvent(
            customerId: $event->id->toString(),
            registeredAt: $event->registeredAt,
        ), $message);
    }
}
