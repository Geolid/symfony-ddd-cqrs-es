<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\Command\CancelOrder;

use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\Command\CancelOrder\CancelOrder;
use Sales\Ordering\Application\Finder\Order\OrderFinderInterface;
use Sales\Ordering\Application\OrderStatus;
use Sales\Ordering\Domain\Order\Exception\OrderBelongsToAnotherShopperException;
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
        $shopperId = Uuid::uuid7()->toString();
        $order = OrderBuilder::new()->withShopperId($shopperId)->create();
        $this->store($order);

        // When
        $this->dispatch(new CancelOrder($order->id->toString(), $shopperId));

        // Then
        $result = $this->finder->ofId($order->id->toString());
        self::assertSame(OrderStatus::CANCELLED, $result->status);
        self::assertNotNull($result->cancelledAt);
    }

    #[Test]
    public function itIgnoresWhenAlreadyCancelled(): void
    {
        // Given
        $shopperId = Uuid::uuid7()->toString();
        $order = OrderBuilder::new()->withShopperId($shopperId)->cancelled()->create();
        $this->store($order);

        // When
        $this->dispatch(new CancelOrder($order->id->toString(), $shopperId));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itCancelsWhenPaymentRequestedButNotCaptured(): void
    {
        // Given
        $shopperId = Uuid::uuid7()->toString();
        $order = OrderBuilder::new()->withShopperId($shopperId)->create();
        $payment = PaymentBuilder::new()->create();
        $this->store($order, $payment);

        // When
        $this->dispatch(new CancelOrder($order->id->toString(), $shopperId));

        // Then
        $result = $this->finder->ofId($order->id->toString());
        self::assertSame(OrderStatus::CANCELLED, $result->status);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();
        $shopperId = Uuid::uuid7()->toString();

        // Then
        $this->expectException(OrderNotFoundException::class);

        // When
        $this->dispatch(new CancelOrder($id, $shopperId));
    }

    #[Test]
    public function itFailsWhenBelongsToAnotherShopper(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $this->store($order);

        // Then
        $this->expectException(OrderBelongsToAnotherShopperException::class);

        // When
        $this->dispatch(new CancelOrder($order->id->toString(), Uuid::uuid7()->toString()));
    }

    #[Test]
    public function itFailsWhenNotCancellable(): void
    {
        // Given
        $shopperId = Uuid::uuid7()->toString();
        $order = OrderBuilder::new()->withShopperId($shopperId)->prepared()->dispatched()->create();
        $this->store($order);

        // Then
        $this->expectException(OrderNotCancellableException::class);

        // When
        $this->dispatch(new CancelOrder($order->id->toString(), $shopperId));
    }
}
