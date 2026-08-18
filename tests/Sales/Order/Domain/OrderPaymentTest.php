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
    public function itIsRequestedForAnOrder(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId);
        $requestedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given()
            ->when(static fn () => OrderPayment::request(
                $id,
                $orderId,
                Money::fromCents(4_200),
                PaymentReference::fromString(self::REFERENCE),
                'https://fake-checkout.test/?ref=GLBX-9F3K2M1P',
                $requestedAt,
            ))
            ->then(self::orderPaymentRequested($id->toString(), $orderId, $requestedAt));
    }

    #[Test]
    public function itIsAuthorizedOnceRequested(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId)->toString();
        $requestedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $authorizedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(self::orderPaymentRequested($id, $orderId, $requestedAt))
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->authorize($authorizedAt))
            ->then(new OrderPaymentAuthorized($id, $orderId, $authorizedAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itDoesNotAuthorizeAnAlreadyAuthorizedPayment(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId)->toString();
        $requestedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $authorizedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(
                self::orderPaymentRequested($id, $orderId, $requestedAt),
                new OrderPaymentAuthorized($id, $orderId, $authorizedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->authorize(new \DateTimeImmutable('2026-01-03T00:00:00+00:00')))
            ->then();
    }

    #[Test]
    public function itVoidsALateAuthorizationOnACancelledPayment(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId)->toString();
        $requestedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $cancelledAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $lateAuthorizedAt = new \DateTimeImmutable('2026-01-03T00:00:00+00:00');

        $this
            ->given(
                self::orderPaymentRequested($id, $orderId, $requestedAt),
                new OrderPaymentCancelled($id, $orderId, $cancelledAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->authorize($lateAuthorizedAt))
            ->then(new OrderPaymentVoided($id, $orderId, self::REFERENCE, $lateAuthorizedAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itIsCapturedOnceAuthorized(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId)->toString();
        $requestedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $authorizedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $capturedAt = new \DateTimeImmutable('2026-01-03T00:00:00+00:00');

        $this
            ->given(
                self::orderPaymentRequested($id, $orderId, $requestedAt),
                new OrderPaymentAuthorized($id, $orderId, $authorizedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->capture($capturedAt))
            ->then(new OrderPaymentCaptured($id, $orderId, $capturedAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itDoesNotCaptureAnUnauthorizedPayment(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId)->toString();
        $requestedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given(self::orderPaymentRequested($id, $orderId, $requestedAt))
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->capture(new \DateTimeImmutable('2026-01-02T00:00:00+00:00')))
            ->then();
    }

    #[Test]
    public function itDoesNotCaptureAnAlreadyCapturedPayment(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId)->toString();
        $requestedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $authorizedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $capturedAt = new \DateTimeImmutable('2026-01-03T00:00:00+00:00');

        $this
            ->given(
                self::orderPaymentRequested($id, $orderId, $requestedAt),
                new OrderPaymentAuthorized($id, $orderId, $authorizedAt->format(\DateTimeInterface::ATOM)),
                new OrderPaymentCaptured($id, $orderId, $capturedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->capture(new \DateTimeImmutable('2026-01-04T00:00:00+00:00')))
            ->then();
    }

    #[Test]
    public function itFailsOnceRequested(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId)->toString();
        $requestedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $failedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(self::orderPaymentRequested($id, $orderId, $requestedAt))
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->fail($failedAt))
            ->then(new OrderPaymentFailed($id, $orderId, $failedAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itDoesNotFailAnAuthorizedPayment(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId)->toString();
        $requestedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $authorizedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(
                self::orderPaymentRequested($id, $orderId, $requestedAt),
                new OrderPaymentAuthorized($id, $orderId, $authorizedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->fail(new \DateTimeImmutable('2026-01-03T00:00:00+00:00')))
            ->then();
    }

    #[Test]
    public function itIsCancelledWhenStillRequested(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId)->toString();
        $requestedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $cancelledAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(self::orderPaymentRequested($id, $orderId, $requestedAt))
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->cancel($cancelledAt))
            ->then(new OrderPaymentCancelled($id, $orderId, $cancelledAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itIsVoidedWhenCancelledAfterAuthorization(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId)->toString();
        $requestedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $authorizedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $cancelledAt = new \DateTimeImmutable('2026-01-03T00:00:00+00:00');

        $this
            ->given(
                self::orderPaymentRequested($id, $orderId, $requestedAt),
                new OrderPaymentAuthorized($id, $orderId, $authorizedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->cancel($cancelledAt))
            ->then(new OrderPaymentVoided($id, $orderId, self::REFERENCE, $cancelledAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itInitiatesARefundWhenCancelledAfterCapture(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId)->toString();
        $requestedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $authorizedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $capturedAt = new \DateTimeImmutable('2026-01-03T00:00:00+00:00');
        $cancelledAt = new \DateTimeImmutable('2026-01-04T00:00:00+00:00');

        $this
            ->given(
                self::orderPaymentRequested($id, $orderId, $requestedAt),
                new OrderPaymentAuthorized($id, $orderId, $authorizedAt->format(\DateTimeInterface::ATOM)),
                new OrderPaymentCaptured($id, $orderId, $capturedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->cancel($cancelledAt))
            ->then(new OrderPaymentRefundInitiated($id, $orderId, self::REFERENCE, $cancelledAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itDoesNotCancelAFailedPayment(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId)->toString();
        $requestedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $failedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(
                self::orderPaymentRequested($id, $orderId, $requestedAt),
                new OrderPaymentFailed($id, $orderId, $failedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->cancel(new \DateTimeImmutable('2026-01-03T00:00:00+00:00')))
            ->then();
    }

    #[Test]
    public function itInitiatesARefundOnceCaptured(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId)->toString();
        $requestedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $authorizedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $capturedAt = new \DateTimeImmutable('2026-01-03T00:00:00+00:00');
        $initiatedAt = new \DateTimeImmutable('2026-01-04T00:00:00+00:00');

        $this
            ->given(
                self::orderPaymentRequested($id, $orderId, $requestedAt),
                new OrderPaymentAuthorized($id, $orderId, $authorizedAt->format(\DateTimeInterface::ATOM)),
                new OrderPaymentCaptured($id, $orderId, $capturedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->initiateRefund($initiatedAt))
            ->then(new OrderPaymentRefundInitiated($id, $orderId, self::REFERENCE, $initiatedAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itDoesNotInitiateARefundOnAnUncapturedPayment(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId)->toString();
        $requestedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $authorizedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(
                self::orderPaymentRequested($id, $orderId, $requestedAt),
                new OrderPaymentAuthorized($id, $orderId, $authorizedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->initiateRefund(new \DateTimeImmutable('2026-01-03T00:00:00+00:00')))
            ->then();
    }

    #[Test]
    public function itConfirmsRefundOnceInitiated(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId)->toString();
        $requestedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $authorizedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $capturedAt = new \DateTimeImmutable('2026-01-03T00:00:00+00:00');
        $refundInitiatedAt = new \DateTimeImmutable('2026-01-04T00:00:00+00:00');
        $refundedAt = new \DateTimeImmutable('2026-01-05T00:00:00+00:00');

        $this
            ->given(
                self::orderPaymentRequested($id, $orderId, $requestedAt),
                new OrderPaymentAuthorized($id, $orderId, $authorizedAt->format(\DateTimeInterface::ATOM)),
                new OrderPaymentCaptured($id, $orderId, $capturedAt->format(\DateTimeInterface::ATOM)),
                new OrderPaymentRefundInitiated($id, $orderId, self::REFERENCE, $refundInitiatedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->confirmRefund($refundedAt))
            ->then(new OrderPaymentRefunded($id, $orderId, $refundedAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itDoesNotConfirmRefundBeforeBeingInitiated(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId)->toString();
        $requestedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $authorizedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $capturedAt = new \DateTimeImmutable('2026-01-03T00:00:00+00:00');

        $this
            ->given(
                self::orderPaymentRequested($id, $orderId, $requestedAt),
                new OrderPaymentAuthorized($id, $orderId, $authorizedAt->format(\DateTimeInterface::ATOM)),
                new OrderPaymentCaptured($id, $orderId, $capturedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->confirmRefund(new \DateTimeImmutable('2026-01-04T00:00:00+00:00')))
            ->then();
    }

    protected function aggregateClass(): string
    {
        return OrderPayment::class;
    }

    private static function orderPaymentRequested(string $id, string $orderId, \DateTimeImmutable $requestedAt): OrderPaymentRequested
    {
        return new OrderPaymentRequested(
            $id,
            $orderId,
            4_200,
            self::REFERENCE,
            'https://fake-checkout.test/?ref=GLBX-9F3K2M1P',
            $requestedAt->format(\DateTimeInterface::ATOM),
        );
    }
}
