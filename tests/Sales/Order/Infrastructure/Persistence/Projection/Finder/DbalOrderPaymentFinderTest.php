<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Persistence\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Enum\OrderPaymentStatus;
use Sales\Order\Application\Exception\OrderPaymentResultNotFoundException;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentResult;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
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
    public function itGetsAnOrderPaymentByItsReference(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $orderPayment = OrderPaymentTestFactory::new()
            ->withOrderId($orderId)
            ->withReference('GLBX-9F3K2M1P')
            ->withAmountInCents(4_200)
            ->withCheckoutUrl('https://fake-checkout.test/?ref=GLBX-9F3K2M1P')
            ->store();

        // When
        $result = $this->finder->ofReference('GLBX-9F3K2M1P');

        // Then
        self::assertSame($orderPayment->id()->toString(), $result->id);
        self::assertSame($orderId, $result->orderId);
        self::assertSame(4_200, $result->amountInCents);
        self::assertSame('GLBX-9F3K2M1P', $result->reference);
        self::assertSame('https://fake-checkout.test/?ref=GLBX-9F3K2M1P', $result->checkoutUrl);
        self::assertSame(OrderPaymentStatus::REQUESTED, $result->status);
        self::assertNull($result->capturedAt);
    }

    #[Test]
    public function itThrowsOnAnUnknownReference(): void
    {
        // Then
        $this->expectException(OrderPaymentResultNotFoundException::class);

        // When
        $this->finder->ofReference('GLBX-NEVER-ISSUED');
    }

    #[Test]
    public function itFindsAnOrderPaymentByItsOrder(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $orderPayment = OrderPaymentTestFactory::new()
            ->withOrderId($orderId)
            ->withReference('GLBX-9F3K2M1P')
            ->withAmountInCents(4_200)
            ->withCheckoutUrl('https://fake-checkout.test/?ref=GLBX-9F3K2M1P')
            ->store();

        // When
        $result = $this->finder->ofOrderOrNull($orderId);

        // Then
        self::assertInstanceOf(OrderPaymentResult::class, $result);
        self::assertSame($orderPayment->id()->toString(), $result->id);
        self::assertSame($orderId, $result->orderId);
        self::assertSame(4_200, $result->amountInCents);
        self::assertSame('GLBX-9F3K2M1P', $result->reference);
        self::assertSame('https://fake-checkout.test/?ref=GLBX-9F3K2M1P', $result->checkoutUrl);
        self::assertSame(OrderPaymentStatus::REQUESTED, $result->status);
        self::assertNull($result->capturedAt);
    }

    #[Test]
    public function itFindsNoPaymentForAnOrderThatNeverRequestedOne(): void
    {
        // When
        $result = $this->finder->ofOrderOrNull(Uuid::uuid7()->toString());

        // Then
        self::assertNull($result);
    }
}
