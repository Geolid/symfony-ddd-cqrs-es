<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Domain;

use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Domain\Event\OrderPaymentCaptured;
use Sales\Order\Domain\Event\OrderPaymentRequested;
use Sales\Order\Domain\Exception\OrderPaymentInvalidTransitionException;
use Sales\Order\Domain\OrderPayment;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
use Shared\Domain\ValueObject\Money;

final class OrderPaymentTest extends AggregateRootTestCase
{
    #[Test]
    public function itIsRequestedForAnOrder(): void
    {
        $id = OrderPaymentId::forOrder('order-1');
        $requestedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given()
            ->when(static fn () => OrderPayment::request(
                $id,
                'order-1',
                'customer-1',
                'buyer@example.com',
                Money::fromCents(4_200),
                'GLBX-9F3K2M1P',
                $requestedAt,
            ))
            ->then(new OrderPaymentRequested(
                $id->toString(),
                'order-1',
                'customer-1',
                'buyer@example.com',
                4_200,
                'GLBX-9F3K2M1P',
                $requestedAt->format('c'),
            ));
    }

    #[Test]
    public function itIsCapturedOnceRequested(): void
    {
        $id = OrderPaymentId::forOrder('order-1')->toString();
        $requestedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $capturedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(new OrderPaymentRequested($id, 'order-1', 'customer-1', 'buyer@example.com', 4_200, 'GLBX-9F3K2M1P', $requestedAt->format('c')))
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->capture($capturedAt))
            ->then(new OrderPaymentCaptured($id, 'order-1', 'customer-1', 'buyer@example.com', $capturedAt->format('c')));
    }

    #[Test]
    public function itCannotBeCapturedTwice(): void
    {
        $id = OrderPaymentId::forOrder('order-1')->toString();
        $requestedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $capturedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(
                new OrderPaymentRequested($id, 'order-1', 'customer-1', 'buyer@example.com', 4_200, 'GLBX-9F3K2M1P', $requestedAt->format('c')),
                new OrderPaymentCaptured($id, 'order-1', 'customer-1', 'buyer@example.com', $capturedAt->format('c')),
            )
            ->when(static fn (OrderPayment $orderPayment) => $orderPayment->capture(new \DateTimeImmutable('2026-01-03T00:00:00+00:00')))
            ->expectsException(OrderPaymentInvalidTransitionException::class);
    }

    protected function aggregateClass(): string
    {
        return OrderPayment::class;
    }
}
