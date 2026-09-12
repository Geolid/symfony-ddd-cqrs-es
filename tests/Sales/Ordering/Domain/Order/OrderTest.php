<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Domain\Order;

use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Domain\Order\Entity\Line;
use Sales\Ordering\Domain\Order\Event\OrderCancelled;
use Sales\Ordering\Domain\Order\Event\OrderConfirmed;
use Sales\Ordering\Domain\Order\Event\OrderDelivered;
use Sales\Ordering\Domain\Order\Event\OrderDispatched;
use Sales\Ordering\Domain\Order\Event\OrderErased;
use Sales\Ordering\Domain\Order\Event\OrderErasureApproved;
use Sales\Ordering\Domain\Order\Event\OrderFailed;
use Sales\Ordering\Domain\Order\Event\OrderPrepared;
use Sales\Ordering\Domain\Order\Exception\OrderBelongsToAnotherShopperException;
use Sales\Ordering\Domain\Order\Exception\OrderNotCancellableException;
use Sales\Ordering\Domain\Order\Exception\OrderWithoutLineException;
use Sales\Ordering\Domain\Order\Order;
use Sales\Ordering\Domain\Order\ValueObject\LineId;
use Sales\Ordering\Domain\Order\ValueObject\OrderId;
use Sales\Ordering\Domain\Order\ValueObject\Product;
use Sales\Ordering\Domain\Order\ValueObject\Quantity;
use Sales\Tests\Ordering\Support\Builder\OrderBuilder;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\PostalAddress;

final class OrderTest extends AggregateRootTestCase
{
    private OrderId $id;
    private readonly string $cartId;
    private string $shopperId;
    private readonly string $checkoutSessionId;
    private PostalAddress $shippingAddress;

    /** @var list<array{product: Product, quantity: Quantity}> */
    private array $lines;

    private \DateTimeImmutable $confirmedAt;
    private \DateTimeImmutable $preparedAt;
    private \DateTimeImmutable $cancelledAt;
    private \DateTimeImmutable $failedAt;
    private \DateTimeImmutable $dispatchedAt;
    private \DateTimeImmutable $deliveredAt;
    private \DateTimeImmutable $erasureApprovedAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = OrderId::fromString(Uuid::uuid7()->toString());
        $this->cartId = OrderBuilder::sample('cartId');
        $this->shopperId = OrderBuilder::sample('shopperId');
        $this->checkoutSessionId = OrderBuilder::sample('checkoutSessionId');
        $this->shippingAddress = OrderBuilder::sample('shippingAddress');
        $this->lines = OrderBuilder::sample('lines');
        $this->confirmedAt = OrderBuilder::sample('confirmedAt');
        $this->preparedAt = OrderBuilder::sample('preparedAt');
        $this->cancelledAt = OrderBuilder::sample('cancelledAt');
        $this->failedAt = OrderBuilder::sample('failedAt');
        $this->dispatchedAt = OrderBuilder::sample('dispatchedAt');
        $this->deliveredAt = OrderBuilder::sample('deliveredAt');
        $this->erasureApprovedAt = OrderBuilder::sample('erasureApprovedAt');
    }

    #[Test]
    public function itConfirms(): void
    {
        $this
            ->given()
            ->when(fn (): Order => Order::confirm($this->id, $this->cartId, $this->shopperId, $this->checkoutSessionId, $this->shippingAddress, $this->lines, $this->confirmedAt))
            ->then(new OrderConfirmed(
                $this->id,
                $this->cartId,
                $this->shopperId,
                $this->checkoutSessionId,
                $this->shippingAddress,
                $this->orderLines(),
                $this->totalAmount(),
                $this->confirmedAt,
            ));
    }

    #[Test]
    public function itCannotConfirmWithoutLine(): void
    {
        $this
            ->given()
            ->when(fn (): Order => Order::confirm($this->id, $this->cartId, $this->shopperId, $this->checkoutSessionId, $this->shippingAddress, [], $this->confirmedAt))
            ->expectsException(OrderWithoutLineException::class);
    }

    #[Test]
    public function itPreparesWhenConfirmed(): void
    {
        $this
            ->given($this->confirmed())
            ->when(fn (Order $order) => $order->prepare($this->preparedAt))
            ->then(new OrderPrepared($this->id, $this->preparedAt));
    }

    #[Test]
    public function itDoesNotPrepareWhenAlreadyPrepared(): void
    {
        $this
            ->given($this->confirmed(), $this->prepared())
            ->when(static fn (Order $order) => $order->prepare(OrderBuilder::sample('preparedAt')))
            ->then();
    }

    #[Test]
    public function itCancels(): void
    {
        $this
            ->given($this->confirmed())
            ->when(fn (Order $order) => $order->cancel($this->shopperId, $this->cancelledAt))
            ->then(new OrderCancelled($this->id, $this->cancelledAt));
    }

    #[Test]
    public function itDoesNotCancelWhenAlreadyCancelled(): void
    {
        $this
            ->given($this->confirmed(), $this->cancelled())
            ->when(fn (Order $order) => $order->cancel($this->shopperId, OrderBuilder::sample('cancelledAt')))
            ->then();
    }

    #[Test]
    public function itCancelsAndErasesWhenErasureApproved(): void
    {
        $this
            ->given($this->confirmed(), $this->erasureApproved())
            ->when(fn (Order $order) => $order->cancel($this->shopperId, $this->cancelledAt))
            ->then(
                new OrderCancelled($this->id, $this->cancelledAt),
                new OrderErased($this->id, $this->cancelledAt),
            );
    }

    #[Test]
    public function itCannotCancelWhenPrepared(): void
    {
        $this
            ->given($this->confirmed(), $this->prepared())
            ->when(fn (Order $order) => $order->cancel($this->shopperId, OrderBuilder::sample('cancelledAt')))
            ->expectsException(OrderNotCancellableException::class);
    }

    #[Test]
    public function itCannotCancelWhenBelongingToAnotherShopper(): void
    {
        $this
            ->given($this->confirmed())
            ->when(fn (Order $order) => $order->cancel(Uuid::uuid7()->toString(), $this->cancelledAt))
            ->expectsException(OrderBelongsToAnotherShopperException::class);
    }

    #[Test]
    public function itFailsWhenConfirmed(): void
    {
        $this
            ->given($this->confirmed())
            ->when(fn (Order $order) => $order->fail($this->failedAt))
            ->then(new OrderFailed($this->id, $this->failedAt));
    }

    #[Test]
    public function itFailsWhenPrepared(): void
    {
        $this
            ->given($this->confirmed(), $this->prepared())
            ->when(fn (Order $order) => $order->fail($this->failedAt))
            ->then(new OrderFailed($this->id, $this->failedAt));
    }

    #[Test]
    public function itFailsAndErasesWhenErasureApproved(): void
    {
        $this
            ->given($this->confirmed(), $this->erasureApproved())
            ->when(fn (Order $order) => $order->fail($this->failedAt))
            ->then(
                new OrderFailed($this->id, $this->failedAt),
                new OrderErased($this->id, $this->failedAt),
            );
    }

    #[Test]
    public function itDoesNotFailWhenDispatched(): void
    {
        $this
            ->given($this->confirmed(), $this->prepared(), $this->dispatched())
            ->when(static fn (Order $order) => $order->fail(OrderBuilder::sample('failedAt')))
            ->then();
    }

    #[Test]
    public function itDispatchesWhenPrepared(): void
    {
        $this
            ->given($this->confirmed(), $this->prepared())
            ->when(fn (Order $order) => $order->dispatch($this->dispatchedAt))
            ->then(new OrderDispatched($this->id, $this->dispatchedAt));
    }

    #[Test]
    public function itDoesNotDispatchWhenNotPrepared(): void
    {
        $this
            ->given($this->confirmed())
            ->when(static fn (Order $order) => $order->dispatch(OrderBuilder::sample('dispatchedAt')))
            ->then();
    }

    #[Test]
    public function itDeliversWhenDispatched(): void
    {
        $this
            ->given($this->confirmed(), $this->prepared(), $this->dispatched())
            ->when(fn (Order $order) => $order->deliver($this->deliveredAt))
            ->then(new OrderDelivered($this->id, $this->deliveredAt));
    }

    #[Test]
    public function itDoesNotDeliverWhenNotDispatched(): void
    {
        $this
            ->given($this->confirmed(), $this->prepared())
            ->when(static fn (Order $order) => $order->deliver(OrderBuilder::sample('deliveredAt')))
            ->then();
    }

    #[Test]
    public function itDeliversAndErasesWhenErasureApproved(): void
    {
        $this
            ->given($this->confirmed(), $this->erasureApproved(), $this->prepared(), $this->dispatched())
            ->when(fn (Order $order) => $order->deliver($this->deliveredAt))
            ->then(
                new OrderDelivered($this->id, $this->deliveredAt),
                new OrderErased($this->id, $this->deliveredAt),
            );
    }

    #[Test]
    public function itApprovesErasure(): void
    {
        $this
            ->given($this->confirmed())
            ->when(fn (Order $order) => $order->approveErasure($this->erasureApprovedAt))
            ->then(new OrderErasureApproved($this->id, $this->erasureApprovedAt));
    }

    #[Test]
    public function itApprovesAndErasesErasureWhenAlreadyDelivered(): void
    {
        $this
            ->given(
                $this->confirmed(),
                $this->prepared(),
                $this->dispatched(),
                new OrderDelivered($this->id, $this->deliveredAt),
            )
            ->when(fn (Order $order) => $order->approveErasure($this->erasureApprovedAt))
            ->then(
                new OrderErasureApproved($this->id, $this->erasureApprovedAt),
                new OrderErased($this->id, $this->erasureApprovedAt),
            );
    }

    #[Test]
    public function itApprovesAndErasesErasureWhenAlreadyCancelled(): void
    {
        $this
            ->given($this->confirmed(), $this->cancelled())
            ->when(fn (Order $order) => $order->approveErasure($this->erasureApprovedAt))
            ->then(
                new OrderErasureApproved($this->id, $this->erasureApprovedAt),
                new OrderErased($this->id, $this->erasureApprovedAt),
            );
    }

    #[Test]
    public function itDoesNotApproveErasureWhenAlreadyApproved(): void
    {
        $this
            ->given($this->confirmed(), $this->erasureApproved())
            ->when(static fn (Order $order) => $order->approveErasure(OrderBuilder::sample('erasureApprovedAt')))
            ->then();
    }

    protected function aggregateClass(): string
    {
        return Order::class;
    }

    private function confirmed(): OrderConfirmed
    {
        return new OrderConfirmed(
            $this->id,
            $this->cartId,
            $this->shopperId,
            $this->checkoutSessionId,
            $this->shippingAddress,
            $this->orderLines(),
            $this->totalAmount(),
            $this->confirmedAt,
        );
    }

    private function prepared(): OrderPrepared
    {
        return new OrderPrepared($this->id, $this->preparedAt);
    }

    private function cancelled(): OrderCancelled
    {
        return new OrderCancelled($this->id, $this->cancelledAt);
    }

    private function dispatched(): OrderDispatched
    {
        return new OrderDispatched($this->id, $this->dispatchedAt);
    }

    private function erasureApproved(): OrderErasureApproved
    {
        return new OrderErasureApproved($this->id, $this->erasureApprovedAt);
    }

    /**
     * @return list<Line>
     */
    private function orderLines(): array
    {
        return array_map(
            fn (array $line, int $position): Line => new Line(LineId::forOrder($this->id->toString(), $position), $line['product'], $line['quantity']),
            $this->lines,
            array_keys($this->lines),
        );
    }

    private function totalAmount(): Money
    {
        return array_reduce(
            $this->orderLines(),
            static fn (Money $carry, Line $line): Money => $carry->plus($line->total()),
            Money::fromCents(0),
        );
    }
}
