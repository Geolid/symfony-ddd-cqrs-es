<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Domain;

use Finance\Payment\Domain\Event\PaymentAbandoned;
use Finance\Payment\Domain\Event\PaymentAuthorized;
use Finance\Payment\Domain\Event\PaymentCaptured;
use Finance\Payment\Domain\Event\PaymentFailed;
use Finance\Payment\Domain\Event\PaymentRequested;
use Finance\Payment\Domain\Event\PaymentVoided;
use Finance\Payment\Domain\Payment;
use Finance\Payment\Domain\ValueObject\PaymentId;
use Finance\Payment\Domain\ValueObject\PaymentReference;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\Money;

final class PaymentTest extends AggregateRootTestCase
{
    private PaymentId $id;
    private string $cartId;
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

        $this->id = PaymentId::fromString(Uuid::uuid7()->toString());
        $this->cartId = PaymentBuilder::sample('cartId');
        $this->orderId = PaymentBuilder::sample('orderId');
        $this->amount = PaymentBuilder::sample('amount');
        $this->reference = PaymentBuilder::sample('reference');
        $this->checkoutUrl = PaymentBuilder::sample('checkoutUrl');
        $this->requestedAt = PaymentBuilder::sample('requestedAt');
        $this->authorizedAt = PaymentBuilder::sample('authorizedAt');
        $this->capturedAt = PaymentBuilder::sample('capturedAt');
    }

    #[Test]
    public function itRequests(): void
    {
        $this
            ->given()
            ->when(fn (): Payment => Payment::request(
                $this->id,
                $this->cartId,
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
            ->then(new PaymentAuthorized($this->id->toString(), $this->cartId, $this->authorizedAt));
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
    public function itVoidsLateAuthorizationWhenAbandoned(): void
    {
        $abandonedAt = PaymentBuilder::sample('abandonedAt');
        $lateAuthorizedAt = PaymentBuilder::sample('authorizedAt');

        $this
            ->given(
                $this->requested(),
                new PaymentAbandoned($this->id->toString(), $this->cartId, $abandonedAt),
            )
            ->when(static fn (Payment $orderPayment) => $orderPayment->authorize($lateAuthorizedAt))
            ->then(new PaymentVoided($this->id->toString(), $this->reference, $lateAuthorizedAt));
    }

    #[Test]
    public function itDoesNotFailWhenRequested(): void
    {
        $this
            ->given($this->requested())
            ->when(fn (Payment $orderPayment) => $orderPayment->fail($this->orderId, PaymentBuilder::sample('failedAt')))
            ->then();
    }

    #[Test]
    public function itFailsWhenAuthorized(): void
    {
        $failedAt = PaymentBuilder::sample('failedAt');

        $this
            ->given($this->requested(), $this->authorized())
            ->when(fn (Payment $orderPayment) => $orderPayment->fail($this->orderId, $failedAt))
            ->then(new PaymentFailed($this->id->toString(), $this->orderId, $failedAt));
    }

    #[Test]
    public function itDoesNotFailWhenCaptured(): void
    {
        $this
            ->given($this->requested(), $this->authorized(), $this->captured())
            ->when(fn (Payment $orderPayment) => $orderPayment->fail($this->orderId, PaymentBuilder::sample('failedAt')))
            ->then();
    }

    #[Test]
    public function itCapturesWhenAuthorized(): void
    {
        $this
            ->given($this->requested(), $this->authorized())
            ->when(fn (Payment $orderPayment) => $orderPayment->capture($this->orderId, $this->capturedAt))
            ->then(new PaymentCaptured($this->id->toString(), $this->orderId, $this->capturedAt));
    }

    #[Test]
    public function itDoesNotCaptureWhenUnauthorized(): void
    {
        $this
            ->given($this->requested())
            ->when(fn (Payment $orderPayment) => $orderPayment->capture($this->orderId, PaymentBuilder::sample('capturedAt')))
            ->then();
    }

    #[Test]
    public function itDoesNotCaptureWhenAlreadyCaptured(): void
    {
        $this
            ->given($this->requested(), $this->authorized(), $this->captured())
            ->when(fn (Payment $orderPayment) => $orderPayment->capture($this->orderId, PaymentBuilder::sample('capturedAt')))
            ->then();
    }

    #[Test]
    public function itAbandonsWhenRequested(): void
    {
        $abandonedAt = PaymentBuilder::sample('abandonedAt');

        $this
            ->given($this->requested())
            ->when(static fn (Payment $orderPayment) => $orderPayment->abandon($abandonedAt))
            ->then(new PaymentAbandoned($this->id->toString(), $this->cartId, $abandonedAt));
    }

    #[Test]
    public function itDoesNotAbandonWhenAuthorized(): void
    {
        $this
            ->given($this->requested(), $this->authorized())
            ->when(static fn (Payment $orderPayment) => $orderPayment->abandon(PaymentBuilder::sample('abandonedAt')))
            ->then();
    }

    #[Test]
    public function itVoidsWhenAuthorized(): void
    {
        $voidedAt = PaymentBuilder::sample('voidedAt');

        $this
            ->given($this->requested(), $this->authorized())
            ->when(static fn (Payment $orderPayment) => $orderPayment->void($voidedAt))
            ->then(new PaymentVoided($this->id->toString(), $this->reference, $voidedAt));
    }

    #[Test]
    public function itDoesNotVoidWhenRequested(): void
    {
        $this
            ->given($this->requested())
            ->when(static fn (Payment $orderPayment) => $orderPayment->void(PaymentBuilder::sample('voidedAt')))
            ->then();
    }

    #[Test]
    public function itDoesNotVoidWhenCaptured(): void
    {
        $this
            ->given($this->requested(), $this->authorized(), $this->captured())
            ->when(static fn (Payment $orderPayment) => $orderPayment->void(PaymentBuilder::sample('voidedAt')))
            ->then();
    }

    #[Test]
    public function itDoesNotVoidWhenFailed(): void
    {
        $this
            ->given(
                $this->requested(),
                $this->authorized(),
                new PaymentFailed($this->id->toString(), $this->orderId, PaymentBuilder::sample('failedAt')),
            )
            ->when(static fn (Payment $orderPayment) => $orderPayment->void(PaymentBuilder::sample('voidedAt')))
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
            $this->cartId,
            $this->amount,
            $this->reference,
            $this->checkoutUrl,
            $this->requestedAt,
        );
    }

    private function authorized(): PaymentAuthorized
    {
        return new PaymentAuthorized($this->id->toString(), $this->cartId, $this->authorizedAt);
    }

    private function captured(): PaymentCaptured
    {
        return new PaymentCaptured($this->id->toString(), $this->orderId, $this->capturedAt);
    }
}
