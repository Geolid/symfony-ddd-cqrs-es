<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Domain;

use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Domain\Event\OrderPaymentAuthorized;
use Sales\Order\Domain\Event\OrderPaymentCancelled;
use Sales\Order\Domain\Event\OrderPaymentCaptured;
use Sales\Order\Domain\Event\OrderPaymentFailed;
use Sales\Order\Domain\Event\OrderPaymentRefunded;
use Sales\Order\Domain\Event\OrderPaymentRefundInitiated;
use Sales\Order\Domain\Event\OrderPaymentRequested;
use Sales\Order\Domain\Event\OrderPaymentVoided;
use Sales\Order\Domain\OrderPayment;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
use Sales\Order\Domain\ValueObject\PaymentReference;
use Sales\Tests\Order\Support\Builder\OrderPaymentBuilder;
use Shared\Domain\ValueObject\Money;

final class OrderPaymentTest extends AggregateRootTestCase
{
    private OrderPaymentId $id;
    private string $orderId;
    private Money $amount;
    private PaymentReference $reference;
    private string $checkoutUrl;
    private \DateTimeImmutable $requestedAt;
    private \DateTimeImmutable $authorizedAt;
    private \DateTimeImmutable $capturedAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderId = OrderPaymentBuilder::sample('orderId');
        $this->id = OrderPaymentId::forOrder($this->orderId);
        $this->amount = OrderPaymentBuilder::sample('amount');
        $this->reference = OrderPaymentBuilder::sample('reference');
        $this->checkoutUrl = OrderPaymentBuilder::sample('checkoutUrl');
        $this->requestedAt = OrderPaymentBuilder::sample('requestedAt');
        $this->authorizedAt = OrderPaymentBuilder::sample('authorizedAt');
        $this->capturedAt = OrderPaymentBuilder::sample('capturedAt');
    }

    #[Test]
    public function itRequestsForOrder(): void
    {
        $this
            ->given()
            ->when(fn (): OrderPayment => OrderPayment::request(
                $this->id,
                $this->orderId,
                $this->amount,
                $this->reference,
                $this->checkoutUrl,
                $this->requestedAt,
            ))
            ->then($this->requested());
    }

    #[Test]
    public function itAuthorizesWhenRequested(): void
    {
        $this
            ->given($this->requested())
            ->when(fn (OrderPayment $orderPayment) => $orderPayment->authorize($this->authorizedAt))
            ->then(new OrderPaymentAuthorized($this->id->toString(), $this->orderId, $this->authorizedAt));
    }

    #[Test]
    public function itDoesNotAuthorizeWhenAlreadyAuthorized(): void
    {
        $this
            ->given($this->requested(), $this->authorized())
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->authorize(OrderPaymentBuilder::sample('authorizedAt')))
            ->then();
    }

    #[Test]
    public function itVoidsLateAuthorizationWhenCancelled(): void
    {
        $cancelledAt = OrderPaymentBuilder::sample('cancelledAt');
        $lateAuthorizedAt = OrderPaymentBuilder::sample('authorizedAt');

        $this
            ->given(
                $this->requested(),
                new OrderPaymentCancelled($this->id->toString(), $this->orderId, $cancelledAt),
            )
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->authorize($lateAuthorizedAt))
            ->then(new OrderPaymentVoided($this->id->toString(), $this->orderId, $this->reference->value, $lateAuthorizedAt));
    }

    #[Test]
    public function itFailsWhenRequested(): void
    {
        $failedAt = OrderPaymentBuilder::sample('failedAt');

        $this
            ->given($this->requested())
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->fail($failedAt))
            ->then(new OrderPaymentFailed($this->id->toString(), $this->orderId, $failedAt));
    }

    #[Test]
    public function itDoesNotFailWhenAuthorized(): void
    {
        $this
            ->given($this->requested(), $this->authorized())
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->fail(OrderPaymentBuilder::sample('failedAt')))
            ->then();
    }

    #[Test]
    public function itCapturesWhenAuthorized(): void
    {
        $this
            ->given($this->requested(), $this->authorized())
            ->when(fn (OrderPayment $orderPayment) => $orderPayment->capture($this->capturedAt))
            ->then(new OrderPaymentCaptured($this->id->toString(), $this->orderId, $this->capturedAt));
    }

    #[Test]
    public function itDoesNotCaptureWhenUnauthorized(): void
    {
        $this
            ->given($this->requested())
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->capture(OrderPaymentBuilder::sample('capturedAt')))
            ->then();
    }

    #[Test]
    public function itDoesNotCaptureWhenAlreadyCaptured(): void
    {
        $this
            ->given($this->requested(), $this->authorized(), $this->captured())
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->capture(OrderPaymentBuilder::sample('capturedAt')))
            ->then();
    }

    #[Test]
    public function itCancelsWhenRequested(): void
    {
        $cancelledAt = OrderPaymentBuilder::sample('cancelledAt');

        $this
            ->given($this->requested())
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->cancel($cancelledAt))
            ->then(new OrderPaymentCancelled($this->id->toString(), $this->orderId, $cancelledAt));
    }

    #[Test]
    public function itVoidsWhenCancelledAfterAuthorization(): void
    {
        $cancelledAt = OrderPaymentBuilder::sample('cancelledAt');

        $this
            ->given($this->requested(), $this->authorized())
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->cancel($cancelledAt))
            ->then(new OrderPaymentVoided($this->id->toString(), $this->orderId, $this->reference->value, $cancelledAt));
    }

    #[Test]
    public function itInitiatesWhenCancelledAfterCapture(): void
    {
        $cancelledAt = OrderPaymentBuilder::sample('cancelledAt');

        $this
            ->given($this->requested(), $this->authorized(), $this->captured())
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->cancel($cancelledAt))
            ->then(new OrderPaymentRefundInitiated($this->id->toString(), $this->orderId, $this->reference->value, $cancelledAt));
    }

    #[Test]
    public function itDoesNotCancelWhenFailed(): void
    {
        $this
            ->given(
                $this->requested(),
                new OrderPaymentFailed($this->id->toString(), $this->orderId, OrderPaymentBuilder::sample('failedAt')),
            )
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->cancel(OrderPaymentBuilder::sample('cancelledAt')))
            ->then();
    }

    #[Test]
    public function itInitiatesWhenCaptured(): void
    {
        $refundInitiatedAt = OrderPaymentBuilder::sample('refundInitiatedAt');

        $this
            ->given($this->requested(), $this->authorized(), $this->captured())
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->initiateRefund($refundInitiatedAt))
            ->then(new OrderPaymentRefundInitiated($this->id->toString(), $this->orderId, $this->reference->value, $refundInitiatedAt));
    }

    #[Test]
    public function itDoesNotInitiateWhenUncaptured(): void
    {
        $this
            ->given($this->requested(), $this->authorized())
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->initiateRefund(OrderPaymentBuilder::sample('refundInitiatedAt')))
            ->then();
    }

    #[Test]
    public function itConfirmsWhenInitiated(): void
    {
        $refundInitiatedAt = OrderPaymentBuilder::sample('refundInitiatedAt');
        $refundConfirmedAt = OrderPaymentBuilder::sample('refundConfirmedAt');

        $this
            ->given(
                $this->requested(),
                $this->authorized(),
                $this->captured(),
                new OrderPaymentRefundInitiated($this->id->toString(), $this->orderId, $this->reference->value, $refundInitiatedAt),
            )
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->confirmRefund($refundConfirmedAt))
            ->then(new OrderPaymentRefunded($this->id->toString(), $this->orderId, $refundConfirmedAt));
    }

    #[Test]
    public function itDoesNotConfirmWhenNotInitiated(): void
    {
        $this
            ->given($this->requested(), $this->authorized(), $this->captured())
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->confirmRefund(OrderPaymentBuilder::sample('refundConfirmedAt')))
            ->then();
    }

    protected function aggregateClass(): string
    {
        return OrderPayment::class;
    }

    private function requested(): OrderPaymentRequested
    {
        return new OrderPaymentRequested(
            $this->id->toString(),
            $this->orderId,
            $this->amount->cents,
            $this->reference->value,
            $this->checkoutUrl,
            $this->requestedAt,
        );
    }

    private function authorized(): OrderPaymentAuthorized
    {
        return new OrderPaymentAuthorized($this->id->toString(), $this->orderId, $this->authorizedAt);
    }

    private function captured(): OrderPaymentCaptured
    {
        return new OrderPaymentCaptured($this->id->toString(), $this->orderId, $this->capturedAt);
    }
}
