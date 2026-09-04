<?php

declare(strict_types=1);

namespace Finance\Payer\Application\IntegrationEvent\PayerErased;

use Finance\Payer\Domain\Event\PayerErased;
use Finance\Payer\Domain\Payer;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('finance.payer.publish_payer_erased')]
final readonly class PayerErasedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(PayerErased::class)]
    public function __invoke(PayerErased $event): void
    {
        $this->publisher->publish(Payer::class, $event->id, new PayerErasedIntegrationEvent(
            payerId: $event->id,
            erasedAt: $event->erasedAt,
        ));
    }
}
