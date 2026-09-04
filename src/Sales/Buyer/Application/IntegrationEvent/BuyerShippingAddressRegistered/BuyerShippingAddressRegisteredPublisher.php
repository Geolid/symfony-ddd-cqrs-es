<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\IntegrationEvent\BuyerShippingAddressRegistered;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Buyer\Domain\Buyer;
use Sales\Buyer\Domain\Event\BuyerShippingAddressRegistered;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('sales.buyer.publish_buyer_shipping_address_registered')]
final readonly class BuyerShippingAddressRegisteredPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(BuyerShippingAddressRegistered::class)]
    public function __invoke(BuyerShippingAddressRegistered $event): void
    {
        $this->publisher->publish(Buyer::class, $event->id, new BuyerShippingAddressRegisteredIntegrationEvent(
            buyerId: $event->id,
            address: $event->address,
            setAt: $event->setAt,
        ));
    }
}
