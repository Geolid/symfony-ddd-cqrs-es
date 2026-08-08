<?php

declare(strict_types=1);

namespace Sales\Tests\OrderSummary\Infrastructure\Persistence\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\OrderSummary\Application\Enum\OrderSummaryStatus;
use Sales\OrderSummary\Application\Exception\OrderSummaryResultNotFoundException;
use Sales\OrderSummary\Application\Finder\OrderSummary\OrderSummaryFinderInterface;
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
        self::assertSame($order->id()->toString(), $result->orderId);
        self::assertSame($customerId, $result->customerId);
        self::assertSame(4_200, $result->totalAmountInCents);
        self::assertSame(OrderSummaryStatus::PAYMENT_PENDING, $result->status);
        self::assertSame(2_500, $result->paymentAmountInCents);
        self::assertSame('GLBX-ABC12345', $result->paymentReference);
        self::assertSame('https://fake-checkout.test/?ref=GLBX-ABC12345', $result->paymentCheckoutUrl);
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
        $order = OrderTestFactory::new()->withCustomerId($customerId)->create();
        $this->store($order);
        $this->store(OrderTestFactory::new()->withCustomerId(Uuid::uuid7()->toString())->create());

        // When
        $results = iterator_to_array($this->finder->withCustomer($customerId));

        // Then
        self::assertCount(1, $results);
        self::assertSame($order->id()->toString(), $results[0]->orderId);
    }

    #[Test]
    public function itFiltersOrderSummariesByStatus(): void
    {
        // Given
        $placed = OrderTestFactory::new()->create();
        $this->store($placed);
        $cancelled = OrderTestFactory::new()->cancelled()->create();
        $this->store($cancelled);

        // When
        $results = iterator_to_array($this->finder->withStatus('cancelled'));

        // Then
        self::assertCount(1, $results);
        self::assertSame($cancelled->id()->toString(), $results[0]->orderId);
    }
}
