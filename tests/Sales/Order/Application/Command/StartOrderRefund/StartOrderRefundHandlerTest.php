<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\StartOrderRefund;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Command\StartOrderRefund\StartOrderRefund;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Status\OrderStatus;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class StartOrderRefundHandlerTest extends AbstractIntegrationTestCase
{
    private OrderFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(OrderFinderInterface::class);
    }

    #[Test]
    public function itStartsTheRefundOnceAReturnHasBeenRequested(): void
    {
        // Given
        $order = OrderTestFactory::new()->confirmed()->dispatched()->completed()->returnRequested()->store();

        // When
        $this->dispatch(new StartOrderRefund($order->id()->toString()));

        // Then
        $result = $this->finder->ofId($order->id()->toString());
        self::assertSame(OrderStatus::REFUNDING, $result->status);
    }

    #[Test]
    public function itIgnoresAnOrderWithNoReturnRequested(): void
    {
        // Given
        $order = OrderTestFactory::new()->confirmed()->dispatched()->completed()->store();

        // When
        $this->dispatch(new StartOrderRefund($order->id()->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenTheOrderDoesNotExist(): void
    {
        // Given
        $id = OrderId::generate()->toString();

        // Then
        $this->expectException(OrderNotFoundException::class);

        // When
        $this->dispatch(new StartOrderRefund($id));
    }
}
