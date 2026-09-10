<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\IntegrationEvent\ShopperShippingAddressDefined;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;
use Shared\Application\Mapper\PostalAddressMapper;
use Shopping\Checkout\Domain\Event\ShopperShippingAddressDefined;
use Shopping\Checkout\Domain\Shopper;

#[Publisher('shopping.checkout.publish_shopper_shipping_address_defined')]
final readonly class ShopperShippingAddressDefinedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(ShopperShippingAddressDefined::class)]
    public function __invoke(ShopperShippingAddressDefined $event): void
    {
        $this->publisher->publish(Shopper::class, $event->id, new ShopperShippingAddressDefinedIntegrationEvent(
            shopperId: $event->id,
            identityId: $event->identityId,
            postalAddress: PostalAddressMapper::toArray($event->postalAddress),
            definedAt: $event->definedAt,
        ));
    }
}
