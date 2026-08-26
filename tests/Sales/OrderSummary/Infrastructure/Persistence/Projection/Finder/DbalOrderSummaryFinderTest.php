<?php

declare(strict_types=1);

namespace Sales\Tests\OrderSummary\Infrastructure\Persistence\Projection\Finder;

use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\OrderSummary\Application\Exception\OrderSummaryResultNotFoundException;
use Sales\OrderSummary\Application\Finder\OrderSummary\OrderSummaryFinderInterface;
use Sales\OrderSummary\Application\Status\OrderSummaryStatus;
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
    public function itGetsTheSummaryForAnOrder(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()->withCustomerId($customerId)->withTotalAmountInCents(4_200)->create();
        $payment = OrderPaymentTestFactory::new()
            ->withOrderId($order->id->toString())
            ->withAmountInCents(2_500)
            ->withReference('GLBX-ABC12345')
            ->withCheckoutUrl('https://fake-checkout.test/?ref=GLBX-ABC12345')
            ->create();
        $shipment = ShipmentTestFactory::new()->withOrderId($order->id->toString())->prepared()->manifested('ACME-4Q7X2K9')->dispatched()->create();
        $this->store($order, $payment, $shipment);

        // When
        $result = $this->finder->ofOrder($order->id->toString());

        // Then
        self::assertSame($order->id->toString(), $result->orderId);
        self::assertSame($customerId, $result->customerId);
        self::assertSame(4_200, $result->totalAmountInCents);
        self::assertSame(OrderSummaryStatus::PAYMENT_PENDING, $result->status);
        self::assertNull($result->cancelledAt);
        self::assertSame(2_500, $result->paymentAmountInCents);
        self::assertSame('GLBX-ABC12345', $result->paymentReference);
        self::assertSame('https://fake-checkout.test/?ref=GLBX-ABC12345', $result->paymentCheckoutUrl);
        self::assertNull($result->paidAt);
        self::assertSame('ACME-4Q7X2K9', $result->trackingReference);
        self::assertNotNull($result->dispatchedAt);
        self::assertNull($result->deliveredAt);
    }

    #[Test]
    public function itThrowsOnAnUnknownOrder(): void
    {
        // Then
        $this->expectException(OrderSummaryResultNotFoundException::class);

        // When
        $this->finder->ofOrder(Uuid::uuid7()->toString());
    }

    #[Test]
    public function itFiltersOrderSummariesByCustomer(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()->withCustomerId($customerId)->withTotalAmountInCents(1_500)->create();
        $other = OrderTestFactory::new()->withCustomerId(Uuid::uuid7()->toString())->create();
        $this->store($order, $other);

        // When
        $results = iterator_to_array($this->finder->byCustomer($customerId));

        // Then
        self::assertCount(1, $results);
        self::assertSame($order->id->toString(), $results[0]->orderId);
        self::assertSame($customerId, $results[0]->customerId);
        self::assertSame(1_500, $results[0]->totalAmountInCents);
        self::assertSame(OrderSummaryStatus::PLACED, $results[0]->status);
    }

    #[Test]
    public function itFiltersOrderSummariesByStatus(): void
    {
        // Given
        $cancelled = OrderTestFactory::new()->cancelled()->create();
        $others = OrderTestFactory::new()->many(2)->create();
        $this->store($cancelled, ...$others);

        // When
        $results = iterator_to_array($this->finder->byStatus('cancelled'));

        // Then
        self::assertCount(1, $results);
        self::assertSame($cancelled->id->toString(), $results[0]->orderId);
        self::assertSame(OrderSummaryStatus::CANCELLED, $results[0]->status);
        self::assertNotNull($results[0]->cancelledAt);
    }
}
