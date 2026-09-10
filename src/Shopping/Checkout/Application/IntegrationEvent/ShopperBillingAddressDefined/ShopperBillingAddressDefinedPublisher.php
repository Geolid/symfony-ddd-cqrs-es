<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\IntegrationEvent\ShopperBillingAddressDefined;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;
use Shared\Application\Mapper\PostalAddressMapper;
use Shopping\Checkout\Domain\Event\ShopperBillingAddressDefined;
use Shopping\Checkout\Domain\Shopper;

#[Publisher('shopping.checkout.publish_shopper_billing_address_defined')]
final readonly class ShopperBillingAddressDefinedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(ShopperBillingAddressDefined::class)]
    public function __invoke(ShopperBillingAddressDefined $event): void
    {
        $this->publisher->publish(Shopper::class, $event->id, new ShopperBillingAddressDefinedIntegrationEvent(
            shopperId: $event->id,
            identityId: $event->identityId,
            postalAddress: PostalAddressMapper::toArray($event->postalAddress),
            definedAt: $event->definedAt,
        ));
    }
}
