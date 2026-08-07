<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Persistence\EventStore\Resolver;

use Catalog\Product\Application\Event\ProductDelistedIntegrationEvent;
use Catalog\Product\Application\Event\ProductListedIntegrationEvent;
use Catalog\Product\Application\Event\ProductRepricedIntegrationEvent;
use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Message\Reducer;
use Patchlevel\EventSourcing\Store\Criteria\Criteria;
use Patchlevel\EventSourcing\Store\Criteria\StreamCriterion;
use Patchlevel\EventSourcing\Store\Store;
use Sales\Order\Application\Product\Product;
use Sales\Order\Application\Product\ProductResolverInterface;
use Shared\Infrastructure\Persistence\EventStore\IntegrationStreamId;

final readonly class ProductResolver implements ProductResolverInterface
{
    public function __construct(private Store $store)
    {
    }

    public function resolveFor(string $productId): ?Product
    {
        $stream = $this->store->load(new Criteria(
            new StreamCriterion(IntegrationStreamId::build('catalog.product', $productId)),
        ));

        /** @var array{label: ?string, unitAmountInCents: ?int, delisted: bool} $state */
        $state = (new Reducer())
            ->initState(['label' => null, 'unitAmountInCents' => null, 'delisted' => false])
            ->when(
                ProductListedIntegrationEvent::class,
                static function (Message $message, array $state): array {
                    /** @var ProductListedIntegrationEvent $event */
                    $event = $message->event();

                    return ['label' => $event->label, 'unitAmountInCents' => $event->unitAmountInCents, 'delisted' => false];
                },
            )
            ->when(
                ProductRepricedIntegrationEvent::class,
                static function (Message $message, array $state): array {
                    /** @var ProductRepricedIntegrationEvent $event */
                    $event = $message->event();

                    return [...$state, 'unitAmountInCents' => $event->unitAmountInCents];
                },
            )
            ->when(
                ProductDelistedIntegrationEvent::class,
                static fn (Message $message, array $state): array => [...$state, 'delisted' => true],
            )
            ->reduce($stream);

        if (null === $state['label'] || null === $state['unitAmountInCents'] || $state['delisted']) {
            return null;
        }

        return new Product($productId, $state['label'], $state['unitAmountInCents']);
    }
}
