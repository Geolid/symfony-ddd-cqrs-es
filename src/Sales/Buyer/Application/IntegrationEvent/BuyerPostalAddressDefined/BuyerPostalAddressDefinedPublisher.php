<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\IntegrationEvent\BuyerPostalAddressDefined;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Buyer\Domain\Buyer;
use Sales\Buyer\Domain\Event\BuyerPostalAddressDefined;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('sales.buyer.publish_buyer_postal_address_defined')]
final readonly class BuyerPostalAddressDefinedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(BuyerPostalAddressDefined::class)]
    public function __invoke(BuyerPostalAddressDefined $event): void
    {
        $this->publisher->publish(Buyer::class, $event->id, new BuyerPostalAddressDefinedIntegrationEvent(
            buyerId: $event->id,
            postalAddress: $event->postalAddress->toArray(),
            definedAt: $event->definedAt,
        ));
    }
}
