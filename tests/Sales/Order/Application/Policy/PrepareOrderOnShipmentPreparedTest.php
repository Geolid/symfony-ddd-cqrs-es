<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Policy;

use Fulfilment\Shipping\Application\IntegrationEvent\ShipmentPrepared\ShipmentPreparedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\OrderStatus;
use Sales\Order\Application\Policy\PrepareOrderOnShipmentPrepared;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class PrepareOrderOnShipmentPreparedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPrepares(): void
    {
        // Given
        $order = OrderBuilder::new()->confirmed()->create();
        $this->store($order);

        // When
        $this->trigger(PrepareOrderOnShipmentPrepared::class, new ShipmentPreparedIntegrationEvent(Uuid::uuid7()->toString(), $order->id->toString(), Clock::get()->now()));

        // Then
        $result = $this->service(OrderFinderInterface::class)->ofId($order->id->toString());
        self::assertSame(OrderStatus::PREPARED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenNotFound(): void
    {
        // When
        $this->trigger(PrepareOrderOnShipmentPrepared::class, new ShipmentPreparedIntegrationEvent(Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), Clock::get()->now()));

        // Then
        self::expectNotToPerformAssertions();
    }
}
