<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Persistence\EventStore\StreamResolver;

use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Message\Reducer;
use Patchlevel\EventSourcing\Store\Criteria\Criteria;
use Patchlevel\EventSourcing\Store\Criteria\StreamCriterion;
use Patchlevel\EventSourcing\Store\Store;
use Sales\Customer\Application\Event\CustomerErasedIntegrationEvent;
use Sales\Customer\Application\Event\CustomerRegisteredIntegrationEvent;
use Sales\Order\Application\Buyer\Buyer;
use Sales\Order\Application\Buyer\BuyerResolverInterface;
use Shared\Infrastructure\Persistence\EventStore\IntegrationStreamId;

final readonly class StreamBuyerResolver implements BuyerResolverInterface
{
    public function __construct(private Store $store)
    {
    }

    public function resolveFor(string $customerId): ?Buyer
    {
        $stream = $this->store->load(new Criteria(
            new StreamCriterion(IntegrationStreamId::build('sales.customer', $customerId)),
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

        if (null === $state['address']) {
            return null;
        }

        return new Buyer($customerId, $state['address']);
    }
}
