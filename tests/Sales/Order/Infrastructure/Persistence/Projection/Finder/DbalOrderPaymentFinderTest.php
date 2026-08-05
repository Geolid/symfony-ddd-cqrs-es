<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Persistence\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
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
    public function itReadsAnOrderPaymentByItsReference(): void
    {
        // Given
        $orderPayment = OrderPaymentTestFactory::new()
            ->withReference('GLBX-9F3K2M1P')
            ->withAmountInCents(4_200)
            ->withCheckoutUrl('https://fake-checkout.test/?ref=GLBX-9F3K2M1P')
            ->create();
        $this->store($orderPayment);

        // When
        $result = $this->finder->ofReference('GLBX-9F3K2M1P');

        // Then
        self::assertInstanceOf(OrderPaymentResult::class, $result);
        self::assertSame($orderPayment->id()->toString(), $result->id);
        self::assertSame($orderPayment->orderId(), $result->orderId);
        self::assertSame(4_200, $result->amountInCents);
        self::assertSame('https://fake-checkout.test/?ref=GLBX-9F3K2M1P', $result->checkoutUrl);
        self::assertSame('requested', $result->status);
        self::assertNull($result->capturedAt);
    }

    #[Test]
    public function itReadsNothingForAReferenceItNeverSaw(): void
    {
        // When
        $result = $this->finder->ofReference('GLBX-NEVER-ISSUED');

        // Then
        self::assertNull($result);
    }

    #[Test]
    public function itReadsAnOrderPaymentByItsOrder(): void
    {
        // Given
        $orderPayment = OrderPaymentTestFactory::new()->withReference('GLBX-9F3K2M1P')->create();
        $this->store($orderPayment);

        // When
        $result = $this->finder->ofOrder($orderPayment->orderId());

        // Then
        self::assertInstanceOf(OrderPaymentResult::class, $result);
        self::assertSame($orderPayment->id()->toString(), $result->id);
        self::assertSame('GLBX-9F3K2M1P', $result->reference);
    }

    #[Test]
    public function itReadsNothingForAnOrderWithoutAPayment(): void
    {
        // When
        $result = $this->finder->ofOrder(Uuid::uuid7()->toString());

        // Then
        self::assertNull($result);
    }
}
