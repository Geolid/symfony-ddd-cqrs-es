<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Domain;

use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Domain\Event\OrderCancelled;
use Sales\Order\Domain\Event\OrderCompleted;
use Sales\Order\Domain\Event\OrderConfirmed;
use Sales\Order\Domain\Event\OrderDelivered;
use Sales\Order\Domain\Event\OrderDispatched;
use Sales\Order\Domain\Event\OrderPlaced;
use Sales\Order\Domain\Exception\OrderAlreadyCancelledException;
use Sales\Order\Domain\Exception\OrderBelongsToAnotherCustomerException;
use Sales\Order\Domain\Exception\OrderNotCancellableException;
use Sales\Order\Domain\Exception\OrderWithoutLineException;
use Sales\Order\Domain\Order;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Order\Domain\ValueObject\OrderLine;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Shared\Domain\ValueObject\PostalAddress;

final class OrderTest extends AggregateRootTestCase
{
    private OrderId $id;
    private string $customerId;
    private PostalAddress $shippingAddress;
    private PostalAddress $billingAddress;

    /** @var list<OrderLine> */
    private array $lines;

    private \DateTimeImmutable $placedAt;
    private \DateTimeImmutable $confirmedAt;
    private \DateTimeImmutable $cancelledAt;
    private \DateTimeImmutable $dispatchedAt;
    private \DateTimeImmutable $deliveredAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = OrderId::generate();
        $this->customerId = OrderBuilder::sample('customerId');
        $this->shippingAddress = OrderBuilder::sample('shippingAddress');
        $this->billingAddress = OrderBuilder::sample('billingAddress');
        $this->lines = OrderBuilder::sample('lines');
        $this->placedAt = OrderBuilder::sample('placedAt');
        $this->confirmedAt = OrderBuilder::sample('confirmedAt');
        $this->cancelledAt = OrderBuilder::sample('cancelledAt');
        $this->dispatchedAt = OrderBuilder::sample('dispatchedAt');
        $this->deliveredAt = OrderBuilder::sample('deliveredAt');
    }

    #[Test]
    public function itPlacesDerivingTotalFromLines(): void
    {
        $this
            ->given()
            ->when(fn (): Order => Order::place($this->id, $this->customerId, $this->shippingAddress, $this->billingAddress, $this->lines, $this->placedAt))
            ->then(new OrderPlaced(
                $this->id->toString(),
                $this->customerId,
                $this->shippingAddress->toArray(),
                $this->billingAddress->toArray(),
                $this->primitiveLines(),
                $this->totalAmountInCents(),
                $this->placedAt,
            ));
    }

    #[Test]
    public function itCannotPlaceWithoutLine(): void
    {
        $this
            ->given()
            ->when(fn (): Order => Order::place($this->id, $this->customerId, $this->shippingAddress, $this->billingAddress, [], $this->placedAt))
            ->expectsException(OrderWithoutLineException::class);
    }

    #[Test]
    public function itConfirmsWhenPlaced(): void
    {
        $this
            ->given($this->placed())
            ->when(fn (Order $order) => $order->confirm($this->confirmedAt))
            ->then(new OrderConfirmed($this->id->toString(), $this->confirmedAt));
    }

    #[Test]
    public function itDoesNotConfirmWhenAlreadyConfirmed(): void
    {
        $this
            ->given($this->placed(), $this->confirmed())
            ->when(static fn (Order $order) => $order->confirm(OrderBuilder::sample('confirmedAt')))
            ->then();
    }

    #[Test]
    public function itCancels(): void
    {
        $this
            ->given($this->placed())
            ->when(fn (Order $order) => $order->cancel($this->customerId, $this->cancelledAt))
            ->then(new OrderCancelled($this->id->toString(), $this->cancelledAt));
    }

    #[Test]
    public function itDoesNotCancelWhenAlreadyCancelled(): void
    {
        $this
            ->given($this->placed(), $this->cancelled())
            ->when(fn (Order $order) => $order->cancel($this->customerId, OrderBuilder::sample('cancelledAt')))
            ->then();
    }

    #[Test]
    public function itCancelsWhenConfirmed(): void
    {
        $this
            ->given($this->placed(), $this->confirmed())
            ->when(fn (Order $order) => $order->cancel($this->customerId, $this->cancelledAt))
            ->then(new OrderCancelled($this->id->toString(), $this->cancelledAt));
    }

    #[Test]
    public function itCannotCancelWhenDispatched(): void
    {
        $this
            ->given($this->placed(), $this->confirmed(), $this->dispatched())
            ->when(fn (Order $order) => $order->cancel($this->customerId, OrderBuilder::sample('cancelledAt')))
            ->expectsException(OrderNotCancellableException::class);
    }

    #[Test]
    public function itCannotCancelWhenBelongingToAnotherCustomer(): void
    {
        $this
            ->given($this->placed())
            ->when(fn (Order $order) => $order->cancel(Uuid::uuid7()->toString(), $this->cancelledAt))
            ->expectsException(OrderBelongsToAnotherCustomerException::class);
    }

    #[Test]
    public function itDispatchesWhenConfirmed(): void
    {
        $this
            ->given($this->placed(), $this->confirmed())
            ->when(fn (Order $order) => $order->dispatch($this->dispatchedAt))
            ->then(new OrderDispatched($this->id->toString(), $this->dispatchedAt));
    }

    #[Test]
    public function itDoesNotDispatchWhenNotConfirmed(): void
    {
        $this
            ->given($this->placed())
            ->when(static fn (Order $order) => $order->dispatch(OrderBuilder::sample('dispatchedAt')))
            ->then();
    }

    #[Test]
    public function itDeliversAndCompletesWhenDispatched(): void
    {
        $this
            ->given($this->placed(), $this->confirmed(), $this->dispatched())
            ->when(fn (Order $order) => $order->deliver($this->deliveredAt))
            ->then(
                new OrderDelivered($this->id->toString(), $this->deliveredAt),
                new OrderCompleted($this->id->toString(), $this->deliveredAt),
            );
    }

    #[Test]
    public function itDoesNotDeliverWhenNotDispatched(): void
    {
        $this
            ->given($this->placed(), $this->confirmed())
            ->when(static fn (Order $order) => $order->deliver(OrderBuilder::sample('deliveredAt')))
            ->then();
    }

    #[Test]
    public function itEnsuresNotCancelled(): void
    {
        $this
            ->given($this->placed())
            ->when(static fn (Order $order) => $order->ensureNotCancelled())
            ->then();
    }

    #[Test]
    public function itCannotEnsureNotCancelledWhenCancelled(): void
    {
        $this
            ->given($this->placed(), $this->cancelled())
            ->when(static fn (Order $order) => $order->ensureNotCancelled())
            ->expectsException(OrderAlreadyCancelledException::class);
    }

    protected function aggregateClass(): string
    {
        return Order::class;
    }

    private function placed(): OrderPlaced
    {
        return new OrderPlaced(
            $this->id->toString(),
            $this->customerId,
            $this->shippingAddress->toArray(),
            $this->billingAddress->toArray(),
            $this->primitiveLines(),
            $this->totalAmountInCents(),
            $this->placedAt,
        );
    }

    private function confirmed(): OrderConfirmed
    {
        return new OrderConfirmed($this->id->toString(), $this->confirmedAt);
    }

    private function cancelled(): OrderCancelled
    {
        return new OrderCancelled($this->id->toString(), $this->cancelledAt);
    }

    private function dispatched(): OrderDispatched
    {
        return new OrderDispatched($this->id->toString(), $this->dispatchedAt);
    }

    private function totalAmountInCents(): int
    {
        return array_sum(array_map(static fn (OrderLine $line): int => $line->total()->cents, $this->lines));
    }

    /**
     * @return list<array{productId: string, label: string, quantity: int, unitAmountInCents: int}>
     */
    private function primitiveLines(): array
    {
        return array_map(
            static fn (OrderLine $line): array => [
                'productId' => $line->product->id,
                'label' => $line->product->label->value,
                'quantity' => $line->quantity,
                'unitAmountInCents' => $line->product->price->cents,
            ],
            $this->lines,
        );
    }
}
