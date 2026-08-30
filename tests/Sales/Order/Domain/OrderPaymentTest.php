<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Domain;

use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
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
use Shared\Domain\ValueObject\Money;

final class OrderPaymentTest extends AggregateRootTestCase
{
    private const string REFERENCE = 'GLBX-9F3K2M1P';

    #[Test]
    public function itRequestsForOrder(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId);
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given()
            ->when(static fn (): OrderPayment => OrderPayment::request(
                $id,
                $orderId,
                Money::fromCents(4_200),
                PaymentReference::fromString(self::REFERENCE),
                'https://checkout.globex.test/pay/GLBX-9F3K2M1P',
                $now,
            ))
            ->then($this->orderPaymentRequested($id->toString(), $orderId, $now));
    }

    #[Test]
    public function itAuthorizesWhenRequested(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId)->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $authorizedAt = $now->modify('+5 minutes');

        $this
            ->given($this->orderPaymentRequested($id, $orderId, $now))
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->authorize($authorizedAt))
            ->then(new OrderPaymentAuthorized($id, $orderId, $authorizedAt));
    }

    #[Test]
    public function itDoesNotAuthorizeWhenAlreadyAuthorized(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId)->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $authorizedAt = $now->modify('+5 minutes');

        $this
            ->given(
                $this->orderPaymentRequested($id, $orderId, $now),
                new OrderPaymentAuthorized($id, $orderId, $authorizedAt),
            )
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->authorize($now->modify('+6 minutes')))
            ->then();
    }

    #[Test]
    public function itVoidsLateAuthorizationWhenCancelled(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId)->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $cancelledAt = $now->modify('+10 minutes');
        $lateAuthorizedAt = $now->modify('+15 minutes');

        $this
            ->given(
                $this->orderPaymentRequested($id, $orderId, $now),
                new OrderPaymentCancelled($id, $orderId, $cancelledAt),
            )
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->authorize($lateAuthorizedAt))
            ->then(new OrderPaymentVoided($id, $orderId, self::REFERENCE, $lateAuthorizedAt));
    }

    #[Test]
    public function itFailsWhenRequested(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId)->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $failedAt = $now->modify('+5 minutes');

        $this
            ->given($this->orderPaymentRequested($id, $orderId, $now))
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->fail($failedAt))
            ->then(new OrderPaymentFailed($id, $orderId, $failedAt));
    }

    #[Test]
    public function itDoesNotFailWhenAuthorized(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId)->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $authorizedAt = $now->modify('+5 minutes');

        $this
            ->given(
                $this->orderPaymentRequested($id, $orderId, $now),
                new OrderPaymentAuthorized($id, $orderId, $authorizedAt),
            )
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->fail($now->modify('+10 minutes')))
            ->then();
    }

    #[Test]
    public function itCapturesWhenAuthorized(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId)->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $authorizedAt = $now->modify('+5 minutes');
        $capturedAt = $now->modify('+1 day');

        $this
            ->given(
                $this->orderPaymentRequested($id, $orderId, $now),
                new OrderPaymentAuthorized($id, $orderId, $authorizedAt),
            )
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->capture($capturedAt))
            ->then(new OrderPaymentCaptured($id, $orderId, $capturedAt));
    }

    #[Test]
    public function itDoesNotCaptureWhenUnauthorized(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId)->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given($this->orderPaymentRequested($id, $orderId, $now))
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->capture($now->modify('+1 hour')))
            ->then();
    }

    #[Test]
    public function itDoesNotCaptureWhenAlreadyCaptured(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId)->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $authorizedAt = $now->modify('+5 minutes');
        $capturedAt = $now->modify('+1 day');

        $this
            ->given(
                $this->orderPaymentRequested($id, $orderId, $now),
                new OrderPaymentAuthorized($id, $orderId, $authorizedAt),
                new OrderPaymentCaptured($id, $orderId, $capturedAt),
            )
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->capture($now->modify('+1 day +1 hour')))
            ->then();
    }

    #[Test]
    public function itCancelsWhenRequested(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId)->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $cancelledAt = $now->modify('+1 hour');

        $this
            ->given($this->orderPaymentRequested($id, $orderId, $now))
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->cancel($cancelledAt))
            ->then(new OrderPaymentCancelled($id, $orderId, $cancelledAt));
    }

    #[Test]
    public function itVoidsWhenCancelledAfterAuthorization(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId)->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $authorizedAt = $now->modify('+5 minutes');
        $cancelledAt = $now->modify('+10 minutes');

        $this
            ->given(
                $this->orderPaymentRequested($id, $orderId, $now),
                new OrderPaymentAuthorized($id, $orderId, $authorizedAt),
            )
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->cancel($cancelledAt))
            ->then(new OrderPaymentVoided($id, $orderId, self::REFERENCE, $cancelledAt));
    }

    #[Test]
    public function itInitiatesWhenCancelledAfterCapture(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId)->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $authorizedAt = $now->modify('+5 minutes');
        $capturedAt = $now->modify('+1 day');
        $cancelledAt = $now->modify('+3 days');

        $this
            ->given(
                $this->orderPaymentRequested($id, $orderId, $now),
                new OrderPaymentAuthorized($id, $orderId, $authorizedAt),
                new OrderPaymentCaptured($id, $orderId, $capturedAt),
            )
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->cancel($cancelledAt))
            ->then(new OrderPaymentRefundInitiated($id, $orderId, self::REFERENCE, $cancelledAt));
    }

    #[Test]
    public function itDoesNotCancelWhenFailed(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId)->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $failedAt = $now->modify('+5 minutes');

        $this
            ->given(
                $this->orderPaymentRequested($id, $orderId, $now),
                new OrderPaymentFailed($id, $orderId, $failedAt),
            )
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->cancel($now->modify('+10 minutes')))
            ->then();
    }

    #[Test]
    public function itInitiatesWhenCaptured(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId)->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $authorizedAt = $now->modify('+5 minutes');
        $capturedAt = $now->modify('+1 day');
        $initiatedAt = $now->modify('+3 days');

        $this
            ->given(
                $this->orderPaymentRequested($id, $orderId, $now),
                new OrderPaymentAuthorized($id, $orderId, $authorizedAt),
                new OrderPaymentCaptured($id, $orderId, $capturedAt),
            )
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->initiateRefund($initiatedAt))
            ->then(new OrderPaymentRefundInitiated($id, $orderId, self::REFERENCE, $initiatedAt));
    }

    #[Test]
    public function itDoesNotInitiateWhenUncaptured(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId)->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $authorizedAt = $now->modify('+5 minutes');

        $this
            ->given(
                $this->orderPaymentRequested($id, $orderId, $now),
                new OrderPaymentAuthorized($id, $orderId, $authorizedAt),
            )
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->initiateRefund($now->modify('+10 minutes')))
            ->then();
    }

    #[Test]
    public function itConfirmsWhenInitiated(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId)->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $authorizedAt = $now->modify('+5 minutes');
        $capturedAt = $now->modify('+1 day');
        $refundInitiatedAt = $now->modify('+3 days');
        $refundedAt = $now->modify('+7 days');

        $this
            ->given(
                $this->orderPaymentRequested($id, $orderId, $now),
                new OrderPaymentAuthorized($id, $orderId, $authorizedAt),
                new OrderPaymentCaptured($id, $orderId, $capturedAt),
                new OrderPaymentRefundInitiated($id, $orderId, self::REFERENCE, $refundInitiatedAt),
            )
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->confirmRefund($refundedAt))
            ->then(new OrderPaymentRefunded($id, $orderId, $refundedAt));
    }

    #[Test]
    public function itDoesNotConfirmWhenNotInitiated(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId)->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $authorizedAt = $now->modify('+5 minutes');
        $capturedAt = $now->modify('+1 day');

        $this
            ->given(
                $this->orderPaymentRequested($id, $orderId, $now),
                new OrderPaymentAuthorized($id, $orderId, $authorizedAt),
                new OrderPaymentCaptured($id, $orderId, $capturedAt),
            )
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->confirmRefund($now->modify('+2 days')))
            ->then();
    }

    protected function aggregateClass(): string
    {
        return OrderPayment::class;
    }

    private function orderPaymentRequested(string $id, string $orderId, \DateTimeImmutable $requestedAt): OrderPaymentRequested
    {
        return new OrderPaymentRequested(
            $id,
            $orderId,
            4_200,
            self::REFERENCE,
            'https://checkout.globex.test/pay/GLBX-9F3K2M1P',
            $requestedAt,
        );
    }
}
