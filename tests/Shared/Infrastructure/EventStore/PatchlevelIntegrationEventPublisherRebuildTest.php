<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\EventStore;

use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Store\Criteria\Criteria;
use Patchlevel\EventSourcing\Store\Criteria\StreamCriterion;
use Patchlevel\EventSourcing\Store\Store;
use Patchlevel\EventSourcing\Subscription\Engine\SubscriptionEngine;
use Patchlevel\EventSourcing\Subscription\Engine\SubscriptionEngineCriteria;
use PHPUnit\Framework\Attributes\Test;
use Sales\Ordering\Application\IntegrationEvent\OrderPrepared\OrderPreparedIntegrationEvent;
use Sales\Ordering\Domain\Order\Order;
use Sales\Tests\Ordering\Support\Factory\OrderFactory;
use Shared\Infrastructure\EventStore\PatchlevelIntegrationEventPublisher;
use Support\TestCase\AbstractIntegrationTestCase;

final class PatchlevelIntegrationEventPublisherRebuildTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishesOnceWhenRebuilt(): void
    {
        // Given
        $order = OrderFactory::new()->prepared()->create();
        $this->store($order);

        // When
        $this->rebuild('sales.ordering.publish_order_prepared');

        // Then
        $stream = PatchlevelIntegrationEventPublisher::streamNameOf(Order::class, $order->id->toString());
        $published = array_filter(
            iterator_to_array($this->service(Store::class)->load(new Criteria(new StreamCriterion($stream)))),
            static fn (Message $message): bool => $message->event() instanceof OrderPreparedIntegrationEvent,
        );
        self::assertCount(1, $published);
    }

    private function rebuild(string $subscriptionId): void
    {
        $engine = $this->service(SubscriptionEngine::class);
        $criteria = new SubscriptionEngineCriteria([$subscriptionId]);

        $engine->remove($criteria);
        $engine->setup($criteria);
        $engine->boot($criteria);
    }
}
