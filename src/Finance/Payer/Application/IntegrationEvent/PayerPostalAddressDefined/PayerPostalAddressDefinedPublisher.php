<?php

declare(strict_types=1);

namespace Finance\Payer\Application\IntegrationEvent\PayerPostalAddressDefined;

use Finance\Payer\Domain\Event\PayerPostalAddressDefined;
use Finance\Payer\Domain\Payer;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('finance.payer.publish_payer_postal_address_defined')]
final readonly class PayerPostalAddressDefinedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(PayerPostalAddressDefined::class)]
    public function __invoke(PayerPostalAddressDefined $event): void
    {
        $this->publisher->publish(Payer::class, $event->id, new PayerPostalAddressDefinedIntegrationEvent(
            payerId: $event->id,
            postalAddress: $event->postalAddress->toArray(),
            definedAt: $event->definedAt,
        ));
    }
}
