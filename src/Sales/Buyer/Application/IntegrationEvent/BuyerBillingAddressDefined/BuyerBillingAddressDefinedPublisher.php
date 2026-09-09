<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\IntegrationEvent\BuyerBillingAddressDefined;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Buyer\Domain\Buyer;
use Sales\Buyer\Domain\Event\BuyerBillingAddressDefined;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;
use Shared\Application\Mapper\PostalAddressMapper;

#[Publisher('sales.buyer.publish_buyer_billing_address_defined')]
final readonly class BuyerBillingAddressDefinedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(BuyerBillingAddressDefined::class)]
    public function __invoke(BuyerBillingAddressDefined $event): void
    {
        $this->publisher->publish(Buyer::class, $event->id, new BuyerBillingAddressDefinedIntegrationEvent(
            buyerId: $event->id,
            identityId: $event->identityId,
            postalAddress: PostalAddressMapper::toArray($event->postalAddress),
            definedAt: $event->definedAt,
        ));
    }
}
