<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\CancelOrphanedOrdersOfBuyer;

use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Command\CancelOrphanedOrdersOfBuyer\CancelOrphanedOrdersOfBuyer;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\OrderStatus;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class CancelOrphanedOrdersOfBuyerHandlerTest extends AbstractIntegrationTestCase
{
    private OrderFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(OrderFinderInterface::class);
    }

    #[Test]
    public function itCancelsWhenCancellable(): void
    {
        // Given
        $otherBuyerId = Uuid::uuid7()->toString();
        $other = OrderBuilder::new()->withBuyerId($otherBuyerId)->create();

        $buyerId = Uuid::uuid7()->toString();
        $alreadyCancelled = OrderBuilder::new()->withBuyerId($buyerId)->cancelled()->create();
        $withCapturedPayment = OrderBuilder::new()->withBuyerId($buyerId)->create();
        $orderPayment = PaymentBuilder::new()->withOrderId($withCapturedPayment->id->toString())->authorized()->captured()->create();
        $placed = OrderBuilder::new()->withBuyerId($buyerId)->create();
        $this->store($other, $alreadyCancelled, $withCapturedPayment, $orderPayment, $placed);

        // When
        $this->dispatch(new CancelOrphanedOrdersOfBuyer($buyerId));

        // Then
        $statusesById = [];
        foreach ($this->finder->byBuyer($buyerId) as $result) {
            $statusesById[$result->id] = $result->status;
        }
        self::assertSame(OrderStatus::CANCELLED, $statusesById[$placed->id->toString()]);
        self::assertSame(OrderStatus::CANCELLED, $statusesById[$alreadyCancelled->id->toString()]);
        self::assertSame(OrderStatus::CANCELLED, $statusesById[$withCapturedPayment->id->toString()]);

        $otherResults = iterator_to_array($this->finder->byBuyer($otherBuyerId), false);
        self::assertSame(OrderStatus::PLACED, $otherResults[0]->status);
    }

    #[Test]
    public function itIgnoresWhenNoneExist(): void
    {
        // Given
        $buyerId = Uuid::uuid7()->toString();
        $otherBuyerId = Uuid::uuid7()->toString();
        $other = OrderBuilder::new()->withBuyerId($otherBuyerId)->create();
        $this->store($other);

        // When
        $this->dispatch(new CancelOrphanedOrdersOfBuyer($buyerId));

        // Then
        $results = iterator_to_array($this->finder->byBuyer($otherBuyerId), false);
        self::assertSame(OrderStatus::PLACED, $results[0]->status);
    }
}
