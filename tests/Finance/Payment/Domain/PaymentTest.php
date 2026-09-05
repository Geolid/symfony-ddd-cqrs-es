<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Domain;

use Finance\Payment\Domain\Event\PaymentAuthorized;
use Finance\Payment\Domain\Event\PaymentCancelled;
use Finance\Payment\Domain\Event\PaymentCaptured;
use Finance\Payment\Domain\Event\PaymentFailed;
use Finance\Payment\Domain\Event\PaymentRefundRequired;
use Finance\Payment\Domain\Event\PaymentRequested;
use Finance\Payment\Domain\Event\PaymentVoided;
use Finance\Payment\Domain\Payment;
use Finance\Payment\Domain\ValueObject\PaymentId;
use Finance\Payment\Domain\ValueObject\PaymentReference;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Shared\Domain\ValueObject\Money;

final class PaymentTest extends AggregateRootTestCase
{
    private PaymentId $id;
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

        $this->orderId = PaymentBuilder::sample('orderId');
        $this->id = PaymentId::forOrder($this->orderId);
        $this->amount = PaymentBuilder::sample('amount');
        $this->reference = PaymentBuilder::sample('reference');
        $this->checkoutUrl = PaymentBuilder::sample('checkoutUrl');
        $this->requestedAt = PaymentBuilder::sample('requestedAt');
        $this->authorizedAt = PaymentBuilder::sample('authorizedAt');
        $this->capturedAt = PaymentBuilder::sample('capturedAt');
    }

    #[Test]
    public function itRequestsForOrder(): void
    {
        $this
            ->given()
            ->when(fn (): Payment => Payment::request(
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
            ->when(fn (Payment $orderPayment) => $orderPayment->authorize($this->authorizedAt))
            ->then(new PaymentAuthorized($this->id->toString(), $this->orderId, $this->authorizedAt));
    }

    #[Test]
    public function itDoesNotAuthorizeWhenAlreadyAuthorized(): void
    {
        $this
            ->given($this->requested(), $this->authorized())
            ->when(static fn (Payment $orderPayment) => $orderPayment->authorize(PaymentBuilder::sample('authorizedAt')))
            ->then();
    }

    #[Test]
    public function itVoidsLateAuthorizationWhenCancelled(): void
    {
        $cancelledAt = PaymentBuilder::sample('cancelledAt');
        $lateAuthorizedAt = PaymentBuilder::sample('authorizedAt');

        $this
            ->given(
                $this->requested(),
                new PaymentCancelled($this->id->toString(), $this->orderId, $cancelledAt),
            )
            ->when(static fn (Payment $orderPayment) => $orderPayment->authorize($lateAuthorizedAt))
            ->then(new PaymentVoided($this->id->toString(), $this->orderId, $this->reference->value, $lateAuthorizedAt));
    }

    #[Test]
    public function itFailsWhenRequested(): void
    {
        $failedAt = PaymentBuilder::sample('failedAt');

        $this
            ->given($this->requested())
            ->when(static fn (Payment $orderPayment) => $orderPayment->fail($failedAt))
            ->then(new PaymentFailed($this->id->toString(), $this->orderId, $failedAt));
    }

    #[Test]
    public function itFailsWhenAuthorized(): void
    {
        $failedAt = PaymentBuilder::sample('failedAt');

        $this
            ->given($this->requested(), $this->authorized())
            ->when(static fn (Payment $orderPayment) => $orderPayment->fail($failedAt))
            ->then(new PaymentFailed($this->id->toString(), $this->orderId, $failedAt));
    }

    #[Test]
    public function itDoesNotFailWhenCaptured(): void
    {
        $this
            ->given($this->requested(), $this->authorized(), $this->captured())
            ->when(static fn (Payment $orderPayment) => $orderPayment->fail(PaymentBuilder::sample('failedAt')))
            ->then();
    }

    #[Test]
    public function itCapturesWhenAuthorized(): void
    {
        $this
            ->given($this->requested(), $this->authorized())
            ->when(fn (Payment $orderPayment) => $orderPayment->capture($this->capturedAt))
            ->then(new PaymentCaptured($this->id->toString(), $this->orderId, $this->capturedAt));
    }

    #[Test]
    public function itDoesNotCaptureWhenUnauthorized(): void
    {
        $this
            ->given($this->requested())
            ->when(static fn (Payment $orderPayment) => $orderPayment->capture(PaymentBuilder::sample('capturedAt')))
            ->then();
    }

    #[Test]
    public function itDoesNotCaptureWhenAlreadyCaptured(): void
    {
        $this
            ->given($this->requested(), $this->authorized(), $this->captured())
            ->when(static fn (Payment $orderPayment) => $orderPayment->capture(PaymentBuilder::sample('capturedAt')))
            ->then();
    }

    #[Test]
    public function itCancelsWhenRequested(): void
    {
        $cancelledAt = PaymentBuilder::sample('cancelledAt');

        $this
            ->given($this->requested())
            ->when(static fn (Payment $orderPayment) => $orderPayment->cancel($cancelledAt))
            ->then(new PaymentCancelled($this->id->toString(), $this->orderId, $cancelledAt));
    }

    #[Test]
    public function itVoidsWhenCancelledAfterAuthorization(): void
    {
        $cancelledAt = PaymentBuilder::sample('cancelledAt');

        $this
            ->given($this->requested(), $this->authorized())
            ->when(static fn (Payment $orderPayment) => $orderPayment->cancel($cancelledAt))
            ->then(new PaymentVoided($this->id->toString(), $this->orderId, $this->reference->value, $cancelledAt));
    }

    #[Test]
    public function itRequiresRefundWhenCancelledAfterCapture(): void
    {
        $cancelledAt = PaymentBuilder::sample('cancelledAt');

        $this
            ->given($this->requested(), $this->authorized(), $this->captured())
            ->when(static fn (Payment $orderPayment) => $orderPayment->cancel($cancelledAt))
            ->then(new PaymentRefundRequired($this->id->toString(), $this->orderId, $this->reference->value, $cancelledAt));
    }

    #[Test]
    public function itDoesNotCancelWhenFailed(): void
    {
        $this
            ->given(
                $this->requested(),
                new PaymentFailed($this->id->toString(), $this->orderId, PaymentBuilder::sample('failedAt')),
            )
            ->when(static fn (Payment $orderPayment) => $orderPayment->cancel(PaymentBuilder::sample('cancelledAt')))
            ->then();
    }

    protected function aggregateClass(): string
    {
        return Payment::class;
    }

    private function requested(): PaymentRequested
    {
        return new PaymentRequested(
            $this->id->toString(),
            $this->orderId,
            $this->amount->cents,
            $this->reference->value,
            $this->checkoutUrl,
            $this->requestedAt,
        );
    }

    private function authorized(): PaymentAuthorized
    {
        return new PaymentAuthorized($this->id->toString(), $this->orderId, $this->authorizedAt);
    }

    private function captured(): PaymentCaptured
    {
        return new PaymentCaptured($this->id->toString(), $this->orderId, $this->capturedAt);
    }
}
