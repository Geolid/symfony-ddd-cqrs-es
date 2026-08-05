<?php

declare(strict_types=1);

namespace Sales\Tests\OrderSummary\Infrastructure\Persistence\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Sales\OrderSummary\Application\Enum\AppOrderSummaryStatus;
use Sales\OrderSummary\Application\Finder\OrderSummary\OrderSummaryFinderInterface;
use Sales\OrderSummary\Application\Finder\OrderSummary\OrderSummaryResult;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class DbalOrderSummaryFinderTest extends AbstractIntegrationTestCase
{
    private OrderSummaryFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(OrderSummaryFinderInterface::class);
    }

    #[Test]
    public function itReadsTheSummaryForAnOrder(): void
    {
        // Given
        $order = OrderTestFactory::new()->withCustomerId('customer-1')->withTotalAmountInCents(4_200)->create();
        $this->store($order);
        $orderPayment = OrderPaymentTestFactory::new()
            ->withOrderId($order->id()->toString())
            ->withAmountInCents(2_500)
            ->withReference('GLBX-ABC12345')
            ->withCheckoutUrl('https://fake-checkout.test/?ref=GLBX-ABC12345')
            ->create();
        $this->store($orderPayment);

        // When
        $result = $this->finder->ofOrder($order->id()->toString());

        // Then
        self::assertInstanceOf(OrderSummaryResult::class, $result);
        self::assertSame($order->id()->toString(), $result->orderId);
        self::assertSame('customer-1', $result->customerId);
        self::assertSame(4_200, $result->totalAmountInCents);
        self::assertSame(AppOrderSummaryStatus::PAYMENT_PENDING, $result->status);
        self::assertSame('placed', $result->orderStatus);
        self::assertSame('requested', $result->paymentStatus);
        self::assertSame(2_500, $result->paymentAmountInCents);
        self::assertSame('GLBX-ABC12345', $result->paymentReference);
        self::assertSame('https://fake-checkout.test/?ref=GLBX-ABC12345', $result->paymentCheckoutUrl);
    }

    #[Test]
    public function itReadsNothingForAnUnknownOrder(): void
    {
        // When
        $result = $this->finder->ofOrder('unknown-order');

        // Then
        self::assertNull($result);
    }

    #[Test]
    public function itFiltersByCustomer(): void
    {
        // Given
        $order = OrderTestFactory::new()->withCustomerId('customer-1')->create();
        $this->store($order);
        $this->store(OrderTestFactory::new()->withCustomerId('customer-2')->create());

        // When
        $results = iterator_to_array($this->finder->withCustomer('customer-1'));

        // Then
        self::assertCount(1, $results);
        self::assertSame($order->id()->toString(), $results[0]->orderId);
    }

    #[Test]
    public function itFiltersByStatus(): void
    {
        // Given
        $placed = OrderTestFactory::new()->create();
        $this->store($placed);
        $cancelled = OrderTestFactory::new()->create();
        $this->store($cancelled);
        $cancelled->cancel(new \DateTimeImmutable('2026-01-02T00:00:00+00:00'));
        $this->store($cancelled);

        // When
        $results = iterator_to_array($this->finder->withStatus('cancelled'));

        // Then
        self::assertCount(1, $results);
        self::assertSame($cancelled->id()->toString(), $results[0]->orderId);
    }
}
