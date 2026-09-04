<?php

declare(strict_types=1);

namespace Finance\Payer\Application\IntegrationEvent\PayerAddressRegistered;

use Finance\Payer\Domain\Event\PayerAddressRegistered;
use Finance\Payer\Domain\Payer;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('finance.payer.publish_payer_address_registered')]
final readonly class PayerAddressRegisteredPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(PayerAddressRegistered::class)]
    public function __invoke(PayerAddressRegistered $event): void
    {
        $this->publisher->publish(Payer::class, $event->id, new PayerAddressRegisteredIntegrationEvent(
            payerId: $event->id,
            address: $event->address,
            setAt: $event->setAt,
        ));
    }
}
