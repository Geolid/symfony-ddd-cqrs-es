<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\Command\CancelOrder;

use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\Command\CancelOrder\CancelOrder;
use Sales\Ordering\Application\Finder\Order\OrderFinderInterface;
use Sales\Ordering\Application\OrderStatus;
use Sales\Ordering\Domain\Order\Exception\OrderBelongsToAnotherBuyerException;
use Sales\Ordering\Domain\Order\Exception\OrderNotCancellableException;
use Sales\Ordering\Domain\Order\Exception\OrderNotFoundException;
use Sales\Tests\Ordering\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class CancelOrderHandlerTest extends AbstractIntegrationTestCase
{
    private OrderFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(OrderFinderInterface::class);
    }

    #[Test]
    public function itCancels(): void
    {
        // Given
        $buyerId = Uuid::uuid7()->toString();
        $order = OrderBuilder::new()->withBuyerId($buyerId)->create();
        $this->store($order);

        // When
        $this->dispatch(new CancelOrder($order->id->toString(), $buyerId));

        // Then
        $result = $this->finder->ofId($order->id->toString());
        self::assertSame(OrderStatus::CANCELLED, $result->status);
        self::assertNotNull($result->cancelledAt);
    }

    #[Test]
    public function itIgnoresWhenAlreadyCancelled(): void
    {
        // Given
        $buyerId = Uuid::uuid7()->toString();
        $order = OrderBuilder::new()->withBuyerId($buyerId)->cancelled()->create();
        $this->store($order);

        // When
        $this->dispatch(new CancelOrder($order->id->toString(), $buyerId));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itCancelsWhenPaymentRequestedButNotCaptured(): void
    {
        // Given
        $buyerId = Uuid::uuid7()->toString();
        $order = OrderBuilder::new()->withBuyerId($buyerId)->create();
        $payment = PaymentBuilder::new()->create();
        $this->store($order, $payment);

        // When
        $this->dispatch(new CancelOrder($order->id->toString(), $buyerId));

        // Then
        $result = $this->finder->ofId($order->id->toString());
        self::assertSame(OrderStatus::CANCELLED, $result->status);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();
        $buyerId = Uuid::uuid7()->toString();

        // Then
        $this->expectException(OrderNotFoundException::class);

        // When
        $this->dispatch(new CancelOrder($id, $buyerId));
    }

    #[Test]
    public function itFailsWhenBelongsToAnotherBuyer(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $this->store($order);

        // Then
        $this->expectException(OrderBelongsToAnotherBuyerException::class);

        // When
        $this->dispatch(new CancelOrder($order->id->toString(), Uuid::uuid7()->toString()));
    }

    #[Test]
    public function itFailsWhenNotCancellable(): void
    {
        // Given
        $buyerId = Uuid::uuid7()->toString();
        $order = OrderBuilder::new()->withBuyerId($buyerId)->prepared()->dispatched()->create();
        $this->store($order);

        // Then
        $this->expectException(OrderNotCancellableException::class);

        // When
        $this->dispatch(new CancelOrder($order->id->toString(), $buyerId));
    }
}
