<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Infrastructure\Persistence\Projection\Reducer;

use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Message\Reducer;
use Patchlevel\EventSourcing\Store\Criteria\Criteria;
use Patchlevel\EventSourcing\Store\Criteria\StreamCriterion;
use Patchlevel\EventSourcing\Store\Store;
use Sales\Order\Application\Event\OrderPlacedIntegrationEvent;

final readonly class OrderSummaryReducer
{
    public function __construct(private Store $store)
    {
    }

    public function forOrder(string $orderId): ?OrderSummary
    {
        $stream = $this->store->load(new Criteria(
            new StreamCriterion(\sprintf('sales.order.integration.%s', $orderId)),
        ));

        /** @var array{customerId: ?string, totalAmountInCents: ?int} $state */
        $state = (new Reducer())
            ->initState(['customerId' => null, 'totalAmountInCents' => null])
            ->when(
                OrderPlacedIntegrationEvent::class,
                static function (Message $message, array $state): array {
                    /** @var OrderPlacedIntegrationEvent $event */
                    $event = $message->event();

                    return ['customerId' => $event->customerId, 'totalAmountInCents' => $event->totalAmountInCents];
                },
            )
            ->reduce($stream);

        if (null === $state['customerId'] || null === $state['totalAmountInCents']) {
            return null;
        }

        return new OrderSummary($state['customerId'], $state['totalAmountInCents']);
    }
}
