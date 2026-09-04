<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\IntegrationEvent\BuyerBillingAddressRegistered;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Buyer\Domain\Buyer;
use Sales\Buyer\Domain\Event\BuyerBillingAddressRegistered;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('sales.buyer.publish_buyer_billing_address_registered')]
final readonly class BuyerBillingAddressRegisteredPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(BuyerBillingAddressRegistered::class)]
    public function __invoke(BuyerBillingAddressRegistered $event): void
    {
        $this->publisher->publish(Buyer::class, $event->id, new BuyerBillingAddressRegisteredIntegrationEvent(
            buyerId: $event->id,
            address: $event->address,
            setAt: $event->setAt,
        ));
    }
}
