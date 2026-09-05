<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Domain;

use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Domain\Event\OrderAborted;
use Sales\Order\Domain\Event\OrderCancelled;
use Sales\Order\Domain\Event\OrderConfirmed;
use Sales\Order\Domain\Event\OrderDelivered;
use Sales\Order\Domain\Event\OrderDispatched;
use Sales\Order\Domain\Event\OrderDisputed;
use Sales\Order\Domain\Event\OrderPlaced;
use Sales\Order\Domain\Event\OrderPrepared;
use Sales\Order\Domain\Event\OrderReturned;
use Sales\Order\Domain\Event\OrderReturnRequested;
use Sales\Order\Domain\Exception\OrderBelongsToAnotherBuyerException;
use Sales\Order\Domain\Exception\OrderNotCancellableException;
use Sales\Order\Domain\Exception\OrderWithoutLineException;
use Sales\Order\Domain\Order;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Order\Domain\ValueObject\OrderLine;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\PostalAddress;

final class OrderTest extends AggregateRootTestCase
{
    private OrderId $id;
    private string $buyerId;
    private PostalAddress $shippingAddress;
    private PostalAddress $billingAddress;

    /** @var list<OrderLine> */
    private array $lines;

    private \DateTimeImmutable $placedAt;
    private \DateTimeImmutable $confirmedAt;
    private \DateTimeImmutable $preparedAt;
    private \DateTimeImmutable $cancelledAt;
    private \DateTimeImmutable $abortedAt;
    private \DateTimeImmutable $dispatchedAt;
    private \DateTimeImmutable $deliveredAt;
    private \DateTimeImmutable $returnRequestedAt;
    private \DateTimeImmutable $returnedAt;
    private \DateTimeImmutable $disputedAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = OrderId::generate();
        $this->buyerId = OrderBuilder::sample('buyerId');
        $this->shippingAddress = OrderBuilder::sample('shippingAddress');
        $this->billingAddress = OrderBuilder::sample('billingAddress');
        $this->lines = OrderBuilder::sample('lines');
        $this->placedAt = OrderBuilder::sample('placedAt');
        $this->confirmedAt = OrderBuilder::sample('confirmedAt');
        $this->preparedAt = OrderBuilder::sample('preparedAt');
        $this->cancelledAt = OrderBuilder::sample('cancelledAt');
        $this->abortedAt = OrderBuilder::sample('abortedAt');
        $this->dispatchedAt = OrderBuilder::sample('dispatchedAt');
        $this->deliveredAt = OrderBuilder::sample('deliveredAt');
        $this->returnRequestedAt = OrderBuilder::sample('returnRequestedAt');
        $this->returnedAt = OrderBuilder::sample('returnedAt');
        $this->disputedAt = OrderBuilder::sample('disputedAt');
    }

    #[Test]
    public function itPlacesDerivingTotalFromLines(): void
    {
        $this
            ->given()
            ->when(fn (): Order => Order::place($this->id, $this->buyerId, $this->shippingAddress, $this->billingAddress, $this->lines, $this->placedAt))
            ->then(new OrderPlaced(
                $this->id->toString(),
                $this->buyerId,
                $this->shippingAddress,
                $this->billingAddress,
                $this->primitiveLines(),
                $this->totalAmount(),
                $this->placedAt,
            ));
    }

    #[Test]
    public function itCannotPlaceWithoutLine(): void
    {
        $this
            ->given()
            ->when(fn (): Order => Order::place($this->id, $this->buyerId, $this->shippingAddress, $this->billingAddress, [], $this->placedAt))
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
    public function itPreparesWhenConfirmed(): void
    {
        $this
            ->given($this->placed(), $this->confirmed())
            ->when(fn (Order $order) => $order->prepare($this->preparedAt))
            ->then(new OrderPrepared($this->id->toString(), $this->preparedAt));
    }

    #[Test]
    public function itDoesNotPrepareWhenNotConfirmed(): void
    {
        $this
            ->given($this->placed())
            ->when(static fn (Order $order) => $order->prepare(OrderBuilder::sample('preparedAt')))
            ->then();
    }

    #[Test]
    public function itCancels(): void
    {
        $this
            ->given($this->placed())
            ->when(fn (Order $order) => $order->cancel($this->buyerId, $this->cancelledAt))
            ->then(new OrderCancelled($this->id->toString(), $this->cancelledAt));
    }

    #[Test]
    public function itDoesNotCancelWhenAlreadyCancelled(): void
    {
        $this
            ->given($this->placed(), $this->cancelled())
            ->when(fn (Order $order) => $order->cancel($this->buyerId, OrderBuilder::sample('cancelledAt')))
            ->then();
    }

    #[Test]
    public function itCancelsWhenConfirmed(): void
    {
        $this
            ->given($this->placed(), $this->confirmed())
            ->when(fn (Order $order) => $order->cancel($this->buyerId, $this->cancelledAt))
            ->then(new OrderCancelled($this->id->toString(), $this->cancelledAt));
    }

    #[Test]
    public function itCannotCancelWhenPrepared(): void
    {
        $this
            ->given($this->placed(), $this->confirmed(), $this->prepared())
            ->when(fn (Order $order) => $order->cancel($this->buyerId, OrderBuilder::sample('cancelledAt')))
            ->expectsException(OrderNotCancellableException::class);
    }

    #[Test]
    public function itCannotCancelWhenBelongingToAnotherBuyer(): void
    {
        $this
            ->given($this->placed())
            ->when(fn (Order $order) => $order->cancel(Uuid::uuid7()->toString(), $this->cancelledAt))
            ->expectsException(OrderBelongsToAnotherBuyerException::class);
    }

    #[Test]
    public function itAbortsWhenPlaced(): void
    {
        $this
            ->given($this->placed())
            ->when(fn (Order $order) => $order->abort($this->abortedAt))
            ->then(new OrderAborted($this->id->toString(), $this->abortedAt));
    }

    #[Test]
    public function itAbortsWhenPrepared(): void
    {
        $this
            ->given($this->placed(), $this->confirmed(), $this->prepared())
            ->when(fn (Order $order) => $order->abort($this->abortedAt))
            ->then(new OrderAborted($this->id->toString(), $this->abortedAt));
    }

    #[Test]
    public function itDoesNotAbortWhenDispatched(): void
    {
        $this
            ->given($this->placed(), $this->confirmed(), $this->prepared(), $this->dispatched())
            ->when(static fn (Order $order) => $order->abort(OrderBuilder::sample('abortedAt')))
            ->then();
    }

    #[Test]
    public function itDispatchesWhenPrepared(): void
    {
        $this
            ->given($this->placed(), $this->confirmed(), $this->prepared())
            ->when(fn (Order $order) => $order->dispatch($this->dispatchedAt))
            ->then(new OrderDispatched($this->id->toString(), $this->dispatchedAt));
    }

    #[Test]
    public function itDoesNotDispatchWhenNotPrepared(): void
    {
        $this
            ->given($this->placed(), $this->confirmed())
            ->when(static fn (Order $order) => $order->dispatch(OrderBuilder::sample('dispatchedAt')))
            ->then();
    }

    #[Test]
    public function itDeliversWhenDispatched(): void
    {
        $this
            ->given($this->placed(), $this->confirmed(), $this->prepared(), $this->dispatched())
            ->when(fn (Order $order) => $order->deliver($this->deliveredAt))
            ->then(new OrderDelivered($this->id->toString(), $this->deliveredAt));
    }

    #[Test]
    public function itDoesNotDeliverWhenNotDispatched(): void
    {
        $this
            ->given($this->placed(), $this->confirmed(), $this->prepared())
            ->when(static fn (Order $order) => $order->deliver(OrderBuilder::sample('deliveredAt')))
            ->then();
    }

    #[Test]
    public function itRequestsReturnWhenDelivered(): void
    {
        $this
            ->given($this->placed(), $this->confirmed(), $this->prepared(), $this->dispatched(), $this->delivered())
            ->when(fn (Order $order) => $order->requestReturn($this->returnRequestedAt))
            ->then(new OrderReturnRequested($this->id->toString(), $this->returnRequestedAt));
    }

    #[Test]
    public function itDoesNotRequestReturnWhenNotDelivered(): void
    {
        $this
            ->given($this->placed(), $this->confirmed(), $this->prepared(), $this->dispatched())
            ->when(static fn (Order $order) => $order->requestReturn(OrderBuilder::sample('returnRequestedAt')))
            ->then();
    }

    #[Test]
    public function itReturnsWhenReturnRequested(): void
    {
        $this
            ->given($this->placed(), $this->confirmed(), $this->prepared(), $this->dispatched(), $this->delivered(), $this->returnRequested())
            ->when(fn (Order $order) => $order->return($this->returnedAt))
            ->then(new OrderReturned($this->id->toString(), $this->returnedAt));
    }

    #[Test]
    public function itDoesNotReturnWhenNotReturnRequested(): void
    {
        $this
            ->given($this->placed(), $this->confirmed(), $this->prepared(), $this->dispatched(), $this->delivered())
            ->when(static fn (Order $order) => $order->return(OrderBuilder::sample('returnedAt')))
            ->then();
    }

    #[Test]
    public function itDisputesWhenReturnRequested(): void
    {
        $this
            ->given($this->placed(), $this->confirmed(), $this->prepared(), $this->dispatched(), $this->delivered(), $this->returnRequested())
            ->when(fn (Order $order) => $order->dispute($this->disputedAt))
            ->then(new OrderDisputed($this->id->toString(), $this->disputedAt));
    }

    #[Test]
    public function itDoesNotDisputeWhenNotReturnRequested(): void
    {
        $this
            ->given($this->placed(), $this->confirmed(), $this->prepared(), $this->dispatched(), $this->delivered())
            ->when(static fn (Order $order) => $order->dispute(OrderBuilder::sample('disputedAt')))
            ->then();
    }

    protected function aggregateClass(): string
    {
        return Order::class;
    }

    private function placed(): OrderPlaced
    {
        return new OrderPlaced(
            $this->id->toString(),
            $this->buyerId,
            $this->shippingAddress,
            $this->billingAddress,
            $this->primitiveLines(),
            $this->totalAmount(),
            $this->placedAt,
        );
    }

    private function confirmed(): OrderConfirmed
    {
        return new OrderConfirmed($this->id->toString(), $this->confirmedAt);
    }

    private function prepared(): OrderPrepared
    {
        return new OrderPrepared($this->id->toString(), $this->preparedAt);
    }

    private function cancelled(): OrderCancelled
    {
        return new OrderCancelled($this->id->toString(), $this->cancelledAt);
    }

    private function dispatched(): OrderDispatched
    {
        return new OrderDispatched($this->id->toString(), $this->dispatchedAt);
    }

    private function delivered(): OrderDelivered
    {
        return new OrderDelivered($this->id->toString(), $this->deliveredAt);
    }

    private function returnRequested(): OrderReturnRequested
    {
        return new OrderReturnRequested($this->id->toString(), $this->returnRequestedAt);
    }

    private function totalAmount(): Money
    {
        return array_reduce(
            $this->lines,
            static fn (Money $carry, OrderLine $line): Money => $carry->plus($line->total()),
            Money::fromCents(0),
        );
    }

    /**
     * @return list<array{productId: string, label: string, quantity: int, unitPriceInCents: int}>
     */
    private function primitiveLines(): array
    {
        return array_map(
            static fn (OrderLine $line): array => [
                'productId' => $line->product->id,
                'label' => $line->product->label->value,
                'quantity' => $line->quantity,
                'unitPriceInCents' => $line->product->price->cents,
            ],
            $this->lines,
        );
    }
}
