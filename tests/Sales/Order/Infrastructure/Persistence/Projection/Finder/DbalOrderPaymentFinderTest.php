<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Persistence\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Exception\OrderPaymentResultNotFoundException;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\Status\OrderPaymentStatus;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class DbalOrderPaymentFinderTest extends AbstractIntegrationTestCase
{
    private OrderPaymentFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(OrderPaymentFinderInterface::class);
    }

    #[Test]
    public function itGetsByReference(): void
    {
        // Given
        $order = OrderTestFactory::new()->store();
        $requestedAt = new \DateTimeImmutable('2026-01-01T08:00:00+00:00');
        $authorizedAt = new \DateTimeImmutable('2026-01-01T09:00:00+00:00');
        $capturedAt = new \DateTimeImmutable('2026-01-02T10:00:00+00:00');
        $refundInitiatedAt = new \DateTimeImmutable('2026-01-03T11:00:00+00:00');
        $refundedAt = new \DateTimeImmutable('2026-01-04T12:00:00+00:00');
        $orderPayment = OrderPaymentTestFactory::new()
            ->withOrderId($order->id()->toString())
            ->withReference('GLBX-9F3K2M1P')
            ->withAmountInCents(4_200)
            ->withCheckoutUrl('https://fake-checkout.test/?ref=GLBX-9F3K2M1P')
            ->withRequestedAt($requestedAt)
            ->authorized($authorizedAt)
            ->captured($capturedAt)
            ->refundInitiated($refundInitiatedAt)
            ->refundConfirmed($refundedAt)
            ->store();

        // When
        $result = $this->finder->ofReference('GLBX-9F3K2M1P');

        // Then
        self::assertSame($orderPayment->id()->toString(), $result->id);
        self::assertSame($order->id()->toString(), $result->orderId);
        self::assertSame(4_200, $result->amountInCents);
        self::assertSame('GLBX-9F3K2M1P', $result->reference);
        self::assertSame('https://fake-checkout.test/?ref=GLBX-9F3K2M1P', $result->checkoutUrl);
        self::assertSame(OrderPaymentStatus::REFUNDED, $result->status);
        self::assertSame($requestedAt->format('Y-m-d H:i:s'), $result->requestedAt->format('Y-m-d H:i:s'));
        self::assertSame($authorizedAt->format('Y-m-d H:i:s'), $result->authorizedAt?->format('Y-m-d H:i:s'));
        self::assertSame($capturedAt->format('Y-m-d H:i:s'), $result->capturedAt?->format('Y-m-d H:i:s'));
        self::assertNull($result->failedAt);
        self::assertNull($result->cancelledAt);
        self::assertSame($refundInitiatedAt->format('Y-m-d H:i:s'), $result->refundInitiatedAt?->format('Y-m-d H:i:s'));
        self::assertSame($refundedAt->format('Y-m-d H:i:s'), $result->refundedAt?->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function itThrowsOnUnknownReference(): void
    {
        // Then
        $this->expectException(OrderPaymentResultNotFoundException::class);

        // When
        $this->finder->ofReference('GLBX-NEVER-ISSUED');
    }
}
