<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Infrastructure\Persistence\EventStore\Reducer;

use Fulfilment\Shipment\Application\Notifier\CustomerAddressResolverInterface;
use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Message\Reducer;
use Patchlevel\EventSourcing\Store\Criteria\Criteria;
use Patchlevel\EventSourcing\Store\Criteria\StreamCriterion;
use Patchlevel\EventSourcing\Store\Store;
use Sales\Customer\Application\Event\CustomerErasedIntegrationEvent;
use Sales\Customer\Application\Event\CustomerRegisteredIntegrationEvent;

final readonly class CustomerAddressResolver implements CustomerAddressResolverInterface
{
    public function __construct(private Store $store)
    {
    }

    public function resolveFor(string $customerId): ?string
    {
        $stream = $this->store->load(new Criteria(
            new StreamCriterion(\sprintf('sales.customer.integration.%s', $customerId)),
        ));

        /** @var array{address: ?string} $state */
        $state = (new Reducer())
            ->initState(['address' => null])
            ->when(
                CustomerRegisteredIntegrationEvent::class,
                static function (Message $message, array $state): array {
                    /** @var CustomerRegisteredIntegrationEvent $event */
                    $event = $message->event();

                    return ['address' => $event->email];
                },
            )
            ->when(
                CustomerErasedIntegrationEvent::class,
                static fn (Message $message, array $state): array => ['address' => null],
            )
            ->reduce($stream);

        return $state['address'];
    }
}
