<?php

declare(strict_types=1);

namespace Finance\Payer\Application\IntegrationEvent\PayerRegistered;

use Finance\Payer\Domain\Event\PayerRegistered;
use Finance\Payer\Domain\Payer;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('finance.payer.publish_payer_registered')]
final readonly class PayerRegisteredPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(PayerRegistered::class)]
    public function __invoke(PayerRegistered $event): void
    {
        $this->publisher->publish(Payer::class, $event->id, new PayerRegisteredIntegrationEvent(
            payerId: $event->id,
            registeredAt: $event->registeredAt,
        ));
    }
}
