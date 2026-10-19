<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\Command\PayOrder;

use PHPUnit\Framework\Attributes\Test;
use Sales\Ordering\Application\Command\PayOrder\PayOrder;
use Sales\Ordering\Application\Finder\Order\OrderFinderInterface;
use Sales\Ordering\Application\OrderStatus;
use Sales\Ordering\Domain\Order\Exception\OrderNotFoundException;
use Sales\Ordering\Domain\Order\Exception\OrderOutOfSequenceException;
use Sales\Tests\Ordering\Support\Factory\OrderFactory;
use Sales\Tests\Ordering\Support\Factory\OrderIdFactory;
use Support\TestCase\AbstractIntegrationTestCase;

final class PayOrderHandlerTest extends AbstractIntegrationTestCase
{
    private OrderFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(OrderFinderInterface::class);
    }

    #[Test]
    public function itPaysWhenPrepared(): void
    {
        // Given
        $order = OrderFactory::new()->prepared()->create();
        $this->store($order);

        // When
        $this->dispatch(new PayOrder($order->id->toString()));

        // Then
        $result = $this->finder->ofId($order->id->toString());
        self::assertSame(OrderStatus::PAID, $result->status);
        self::assertNotNull($result->paidAt);
    }

    #[Test]
    public function itIgnoresWhenCancelled(): void
    {
        // Given
        $order = OrderFactory::new()->cancelled()->create();
        $this->store($order);

        // When
        $this->dispatch(new PayOrder($order->id->toString()));

        // Then
        $result = $this->finder->ofId($order->id->toString());
        self::assertSame(OrderStatus::CANCELLED, $result->status);
    }

    #[Test]
    public function itFailsWhenNotPrepared(): void
    {
        // Given
        $order = OrderFactory::new()->create();
        $this->store($order);

        // Then
        $this->expectException(OrderOutOfSequenceException::class);

        // When
        $this->dispatch(new PayOrder($order->id->toString()));
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $unknownId = OrderIdFactory::new()->create()->toString();

        // Then
        $this->expectException(OrderNotFoundException::class);

        // When
        $this->dispatch(new PayOrder($unknownId));
    }
}
