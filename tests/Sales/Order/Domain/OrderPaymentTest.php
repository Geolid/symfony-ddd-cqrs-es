<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Domain;

use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Domain\Event\OrderPaymentCaptured;
use Sales\Order\Domain\Event\OrderPaymentRequested;
use Sales\Order\Domain\OrderPayment;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
use Sales\Order\Domain\ValueObject\PaymentReference;
use Shared\Domain\ValueObject\Money;

final class OrderPaymentTest extends AggregateRootTestCase
{
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
                PaymentReference::fromString('GLBX-9F3K2M1P'),
                'https://fake-checkout.test/?ref=GLBX-9F3K2M1P',
                $requestedAt,
            ))
            ->then(self::orderPaymentRequested($id->toString(), $orderId, $requestedAt));
    }

    #[Test]
    public function itIsCapturedOnceRequested(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId)->toString();
        $requestedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $capturedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(self::orderPaymentRequested($id, $orderId, $requestedAt))
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->capture($capturedAt))
            ->then(new OrderPaymentCaptured($id, $orderId, $capturedAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itDoesNotCaptureAnAlreadyCapturedPayment(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $id = OrderPaymentId::forOrder($orderId)->toString();
        $requestedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $capturedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(
                self::orderPaymentRequested($id, $orderId, $requestedAt),
                new OrderPaymentCaptured($id, $orderId, $capturedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->capture(new \DateTimeImmutable('2026-01-03T00:00:00+00:00')))
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
            'GLBX-9F3K2M1P',
            'https://fake-checkout.test/?ref=GLBX-9F3K2M1P',
            $requestedAt->format(\DateTimeInterface::ATOM),
        );
    }
}
