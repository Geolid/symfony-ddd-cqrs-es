<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\IntegrationEvent\BuyerShippingAddressDefined;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Buyer\Domain\Buyer;
use Sales\Buyer\Domain\Event\BuyerShippingAddressDefined;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('sales.buyer.publish_buyer_shipping_address_defined')]
final readonly class BuyerShippingAddressDefinedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(BuyerShippingAddressDefined::class)]
    public function __invoke(BuyerShippingAddressDefined $event): void
    {
        $this->publisher->publish(Buyer::class, $event->id, new BuyerShippingAddressDefinedIntegrationEvent(
            buyerId: $event->id,
            identityId: $event->identityId,
            postalAddress: $event->postalAddress->toArray(),
            definedAt: $event->definedAt,
        ));
    }
}
