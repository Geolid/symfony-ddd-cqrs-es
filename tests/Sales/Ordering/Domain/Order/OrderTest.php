<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Domain\Order;

use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Domain\Order\Entity\OrderLine;
use Sales\Ordering\Domain\Order\Event\OrderCancelled;
use Sales\Ordering\Domain\Order\Event\OrderConfirmed;
use Sales\Ordering\Domain\Order\Event\OrderDelivered;
use Sales\Ordering\Domain\Order\Event\OrderDispatched;
use Sales\Ordering\Domain\Order\Event\OrderErased;
use Sales\Ordering\Domain\Order\Event\OrderErasureApproved;
use Sales\Ordering\Domain\Order\Event\OrderFailed;
use Sales\Ordering\Domain\Order\Event\OrderPrepared;
use Sales\Ordering\Domain\Order\Exception\OrderBelongsToAnotherCustomerException;
use Sales\Ordering\Domain\Order\Exception\OrderNotCancellableException;
use Sales\Ordering\Domain\Order\Exception\OrderWithoutLineException;
use Sales\Ordering\Domain\Order\Order;
use Sales\Ordering\Domain\Order\ValueObject\OrderId;
use Sales\Ordering\Domain\Order\ValueObject\OrderItem;
use Sales\Ordering\Domain\Order\ValueObject\OrderLineId;
use Sales\Tests\Ordering\Support\Factory\OrderIdFactory;
use Sales\Tests\Ordering\Support\Factory\OrderItemFactory;
use Shared\Domain\ValueObject\Currency;
use Shared\Domain\ValueObject\PostalAddress;
use Shared\Domain\ValueObject\TaxedAmount;
use Shared\Tests\Support\Factory\PostalAddressFactory;
use Symfony\Component\Clock\Clock;

final class OrderTest extends AggregateRootTestCase
{
    private OrderId $id;
    private readonly string $cartId;
    private string $customerId;
    private readonly string $checkoutSessionId;
    private PostalAddress $shippingAddress;

    /** @var list<OrderItem> */
    private readonly array $items;

    private Currency $currency;

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

        $this->id = OrderIdFactory::new()->create();
        $this->cartId = Uuid::uuid7()->toString();
        $this->customerId = Uuid::uuid7()->toString();
        $this->checkoutSessionId = Uuid::uuid7()->toString();
        $this->shippingAddress = PostalAddressFactory::new()->create();
        $this->items = OrderItemFactory::new()->many(2)->create();
        $this->currency = Currency::EUR;
        $now = Clock::get()->now();
        $this->confirmedAt = $now;
        $this->preparedAt = $now->modify('+1 day');
        $this->cancelledAt = $now->modify('+1 day');
        $this->failedAt = $now->modify('+1 day');
        $this->dispatchedAt = $now->modify('+2 day');
        $this->deliveredAt = $now->modify('+3 day');
        $this->erasureApprovedAt = $now->modify('+4 day');
    }

    #[Test]
    public function itConfirms(): void
    {
        $this
            ->given()
            ->when(fn (): Order => Order::confirm($this->id, $this->cartId, $this->customerId, $this->checkoutSessionId, $this->shippingAddress, $this->items, $this->currency, $this->confirmedAt))
            ->then(new OrderConfirmed(
                $this->id,
                $this->cartId,
                $this->customerId,
                $this->checkoutSessionId,
                $this->shippingAddress,
                $this->orderLines(),
                $this->total(),
                $this->confirmedAt,
            ));
    }

    #[Test]
    public function itCannotConfirmWithoutLine(): void
    {
        $this
            ->given()
            ->when(fn (): Order => Order::confirm($this->id, $this->cartId, $this->customerId, $this->checkoutSessionId, $this->shippingAddress, [], $this->currency, $this->confirmedAt))
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
            ->when(static fn (Order $order) => $order->prepare(Clock::get()->now()->modify('+1 day')))
            ->then();
    }

    #[Test]
    public function itCancels(): void
    {
        $this
            ->given($this->confirmed())
            ->when(fn (Order $order) => $order->cancel($this->customerId, $this->cancelledAt))
            ->then(new OrderCancelled($this->id, $this->cancelledAt));
    }

    #[Test]
    public function itDoesNotCancelWhenAlreadyCancelled(): void
    {
        $this
            ->given($this->confirmed(), $this->cancelled())
            ->when(fn (Order $order) => $order->cancel($this->customerId, Clock::get()->now()->modify('+1 day')))
            ->then();
    }

    #[Test]
    public function itCancelsAndErasesWhenErasureApproved(): void
    {
        $this
            ->given($this->confirmed(), $this->erasureApproved())
            ->when(fn (Order $order) => $order->cancel($this->customerId, $this->cancelledAt))
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
            ->when(fn (Order $order) => $order->cancel($this->customerId, Clock::get()->now()->modify('+1 day')))
            ->expectsException(OrderNotCancellableException::class);
    }

    #[Test]
    public function itCannotCancelWhenBelongingToAnotherCustomer(): void
    {
        $this
            ->given($this->confirmed())
            ->when(fn (Order $order) => $order->cancel(Uuid::uuid7()->toString(), $this->cancelledAt))
            ->expectsException(OrderBelongsToAnotherCustomerException::class);
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
            ->when(static fn (Order $order) => $order->fail(Clock::get()->now()->modify('+1 day')))
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
            ->when(static fn (Order $order) => $order->dispatch(Clock::get()->now()->modify('+2 day')))
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
            ->when(static fn (Order $order) => $order->deliver(Clock::get()->now()->modify('+3 day')))
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
            ->when(static fn (Order $order) => $order->approveErasure(Clock::get()->now()->modify('+4 day')))
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
            $this->customerId,
            $this->checkoutSessionId,
            $this->shippingAddress,
            $this->orderLines(),
            $this->total(),
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
     * @return list<OrderLine>
     */
    private function orderLines(): array
    {
        return array_map(
            fn (OrderItem $item, int $position): OrderLine => new OrderLine(OrderLineId::forOrder($this->id->toString(), $position), $item),
            $this->items,
            array_keys($this->items),
        );
    }

    private function total(): TaxedAmount
    {
        return array_reduce(
            $this->orderLines(),
            static fn (TaxedAmount $carry, OrderLine $line): TaxedAmount => $carry->plus($line->taxedTotal()),
            TaxedAmount::zero($this->currency),
        );
    }
}
