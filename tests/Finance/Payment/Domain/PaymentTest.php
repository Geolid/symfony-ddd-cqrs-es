<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Domain;

use Finance\Payment\Domain\Event\PaymentAbandoned;
use Finance\Payment\Domain\Event\PaymentAuthorized;
use Finance\Payment\Domain\Event\PaymentCaptured;
use Finance\Payment\Domain\Event\PaymentFailed;
use Finance\Payment\Domain\Event\PaymentRequested;
use Finance\Payment\Domain\Event\PaymentVoided;
use Finance\Payment\Domain\Exception\PaymentInvalidStateException;
use Finance\Payment\Domain\Payment;
use Finance\Payment\Domain\ValueObject\PaymentId;
use Finance\Payment\Domain\ValueObject\PaymentReference;
use Finance\Tests\Payment\Support\Factory\PaymentIdFactory;
use Finance\Tests\Payment\Support\Factory\PaymentReferenceFactory;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\Money;
use Shared\Tests\Support\Factory\MoneyFactory;
use Symfony\Component\Clock\Clock;

final class PaymentTest extends AggregateRootTestCase
{
    private PaymentId $id;
    private string $checkoutSessionId;
    private string $orderId;
    private Money $amount;
    private PaymentReference $reference;
    private string $hostedPageUrl;
    private \DateTimeImmutable $requestedAt;
    private \DateTimeImmutable $authorizedAt;
    private \DateTimeImmutable $capturedAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = PaymentIdFactory::new()->create();
        $this->checkoutSessionId = Uuid::uuid7()->toString();
        $this->orderId = Uuid::uuid7()->toString();
        $this->amount = MoneyFactory::new()->create();
        $this->reference = PaymentReferenceFactory::new()->create();
        $this->hostedPageUrl = \sprintf('https://checkout.globex.test/pay/%s', $this->reference->value);
        $this->requestedAt = Clock::get()->now();
        $this->authorizedAt = $this->requestedAt->modify('+1 day');
        $this->capturedAt = $this->requestedAt->modify('+2 day');
    }

    #[Test]
    public function itRequests(): void
    {
        $this
            ->given()
            ->when(fn (): Payment => Payment::request(
                $this->id,
                $this->checkoutSessionId,
                $this->amount,
                $this->reference,
                $this->hostedPageUrl,
                $this->requestedAt,
            ))
            ->then($this->requested());
    }

    #[Test]
    public function itAuthorizesWhenRequested(): void
    {
        $this
            ->given($this->requested())
            ->when(fn (Payment $payment) => $payment->authorize($this->authorizedAt))
            ->then(new PaymentAuthorized($this->id, $this->checkoutSessionId, $this->authorizedAt));
    }

    #[Test]
    public function itDoesNotAuthorizeWhenAlreadyAuthorized(): void
    {
        $this
            ->given($this->requested(), $this->authorized())
            ->when(fn (Payment $payment) => $payment->authorize($this->authorizedAt))
            ->then();
    }

    #[Test]
    public function itVoidsLateAuthorizationWhenAbandoned(): void
    {
        $now = Clock::get()->now();
        $abandonedAt = $now->modify('+1 day');
        $lateAuthorizedAt = $abandonedAt->modify('+1 hour');

        $this
            ->given(
                $this->requested(),
                new PaymentAbandoned($this->id, $this->checkoutSessionId, $abandonedAt),
            )
            ->when(static fn (Payment $payment) => $payment->authorize($lateAuthorizedAt))
            ->then(new PaymentVoided($this->id, $this->reference, $lateAuthorizedAt));
    }

    #[Test]
    public function itCannotFailWhenRequested(): void
    {
        $this
            ->given($this->requested())
            ->when(fn (Payment $payment) => $payment->fail($this->orderId, $this->authorizedAt))
            ->expectsException(PaymentInvalidStateException::class);
    }

    #[Test]
    public function itFailsWhenAuthorized(): void
    {
        $failedAt = $this->authorizedAt->modify('+1 hour');

        $this
            ->given($this->requested(), $this->authorized())
            ->when(fn (Payment $payment) => $payment->fail($this->orderId, $failedAt))
            ->then(new PaymentFailed($this->id, $this->orderId, $failedAt));
    }

    #[Test]
    public function itCannotFailWhenCaptured(): void
    {
        $this
            ->given($this->requested(), $this->authorized(), $this->captured())
            ->when(fn (Payment $payment) => $payment->fail($this->orderId, $this->authorizedAt))
            ->expectsException(PaymentInvalidStateException::class);
    }

    #[Test]
    public function itCapturesWhenAuthorized(): void
    {
        $this
            ->given($this->requested(), $this->authorized())
            ->when(fn (Payment $payment) => $payment->capture($this->orderId, $this->capturedAt))
            ->then(new PaymentCaptured($this->id, $this->orderId, $this->capturedAt));
    }

    #[Test]
    public function itCannotCaptureWhenUnauthorized(): void
    {
        $this
            ->given($this->requested())
            ->when(fn (Payment $payment) => $payment->capture($this->orderId, $this->capturedAt))
            ->expectsException(PaymentInvalidStateException::class);
    }

    #[Test]
    public function itDoesNotCaptureWhenAlreadyCaptured(): void
    {
        $this
            ->given($this->requested(), $this->authorized(), $this->captured())
            ->when(fn (Payment $payment) => $payment->capture($this->orderId, $this->capturedAt))
            ->then();
    }

    #[Test]
    public function itAbandonsWhenRequested(): void
    {
        $abandonedAt = $this->requestedAt->modify('+1 hour');

        $this
            ->given($this->requested())
            ->when(static fn (Payment $payment) => $payment->abandon($abandonedAt))
            ->then(new PaymentAbandoned($this->id, $this->checkoutSessionId, $abandonedAt));
    }

    #[Test]
    public function itDoesNotAbandonWhenAuthorized(): void
    {
        $this
            ->given($this->requested(), $this->authorized())
            ->when(fn (Payment $payment) => $payment->abandon($this->authorizedAt))
            ->then();
    }

    #[Test]
    public function itVoidsWhenAuthorized(): void
    {
        $voidedAt = $this->authorizedAt->modify('+1 hour');

        $this
            ->given($this->requested(), $this->authorized())
            ->when(static fn (Payment $payment) => $payment->void($voidedAt))
            ->then(new PaymentVoided($this->id, $this->reference, $voidedAt));
    }

    #[Test]
    public function itDoesNotVoidWhenRequested(): void
    {
        $this
            ->given($this->requested())
            ->when(fn (Payment $payment) => $payment->void($this->authorizedAt))
            ->then();
    }

    #[Test]
    public function itDoesNotVoidWhenCaptured(): void
    {
        $this
            ->given($this->requested(), $this->authorized(), $this->captured())
            ->when(fn (Payment $payment) => $payment->void($this->authorizedAt))
            ->then();
    }

    #[Test]
    public function itDoesNotVoidWhenFailed(): void
    {
        $failedAt = $this->authorizedAt->modify('+1 hour');
        $voidedAt = $failedAt->modify('+1 hour');

        $this
            ->given(
                $this->requested(),
                $this->authorized(),
                new PaymentFailed($this->id, $this->orderId, $failedAt),
            )
            ->when(static fn (Payment $payment) => $payment->void($voidedAt))
            ->then();
    }

    protected function aggregateClass(): string
    {
        return Payment::class;
    }

    private function requested(): PaymentRequested
    {
        return new PaymentRequested(
            $this->id,
            $this->checkoutSessionId,
            $this->amount,
            $this->reference,
            $this->hostedPageUrl,
            $this->requestedAt,
        );
    }

    private function authorized(): PaymentAuthorized
    {
        return new PaymentAuthorized($this->id, $this->checkoutSessionId, $this->authorizedAt);
    }

    private function captured(): PaymentCaptured
    {
        return new PaymentCaptured($this->id, $this->orderId, $this->capturedAt);
    }
}
