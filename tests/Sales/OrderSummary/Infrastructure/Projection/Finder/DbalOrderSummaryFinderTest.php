<?php

declare(strict_types=1);

namespace Sales\Tests\OrderSummary\Infrastructure\Projection\Finder;

use Fulfilment\Tests\Shipment\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Domain\Order;
use Sales\OrderSummary\Application\Exception\OrderSummaryResultNotFoundException;
use Sales\OrderSummary\Application\Finder\OrderSummary\OrderSummaryFinderInterface;
use Sales\OrderSummary\Application\Finder\OrderSummary\OrderSummaryResult;
use Sales\OrderSummary\Application\OrderSummaryStatus;
use Sales\Tests\Order\Support\Builder\OrderPaymentBuilder;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
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
    public function itGetsByOrder(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderBuilder::new()->withCustomerId($customerId)->withTotalAmountInCents(4_200)->create();
        $payment = OrderPaymentBuilder::new()
            ->withOrderId($order->id->toString())
            ->withAmountInCents(2_500)
            ->withReference('GLBX-ABC12345')
            ->withCheckoutUrl('https://checkout.globex.test/pay/GLBX-ABC12345')
            ->create();
        $shipment = ShipmentBuilder::new()->withOrderId($order->id->toString())->prepared()->manifested('ACME-4Q7X2K9')->dispatched()->create();
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
        self::assertSame('https://checkout.globex.test/pay/GLBX-ABC12345', $result->paymentCheckoutUrl);
        self::assertNull($result->paidAt);
        self::assertSame('ACME-4Q7X2K9', $result->trackingReference);
        self::assertNotNull($result->dispatchedAt);
        self::assertNull($result->deliveredAt);
    }

    #[Test]
    public function itThrowsWhenOrderNotFound(): void
    {
        // Then
        $this->expectException(OrderSummaryResultNotFoundException::class);

        // When
        $this->finder->ofOrder(Uuid::uuid7()->toString());
    }

    #[Test]
    public function itFiltersByCustomer(): void
    {
        // Given
        $other = OrderBuilder::new()->withCustomerId(Uuid::uuid7()->toString())->create();
        $customerId = Uuid::uuid7()->toString();
        $order = OrderBuilder::new()->withCustomerId($customerId)->create();
        $this->store($other, $order);

        // When
        $results = iterator_to_array($this->finder->byCustomer($customerId));

        // Then
        self::assertCount(1, $results);
        self::assertSame($order->id->toString(), $results[0]->orderId);
    }

    #[Test]
    public function itFiltersByStatus(): void
    {
        // Given
        $others = OrderBuilder::new()->many(2)->create();
        $cancelled = OrderBuilder::new()->cancelled()->create();
        $orders = [...$others, $cancelled];
        $this->store(...$orders);

        // When
        $results = iterator_to_array($this->finder->byStatus(OrderSummaryStatus::CANCELLED));

        // Then
        self::assertCount(1, $results);
        self::assertSame($cancelled->id->toString(), $results[0]->orderId);
    }

    #[Test]
    public function itLists(): void
    {
        // Given
        $orders = OrderBuilder::new()->many(5)->create();
        $this->store(...$orders);

        // When
        $results = iterator_to_array($this->finder);

        // Then
        self::assertSame($this->orderIds(...$orders), $this->resultIds($results));
    }

    #[Test]
    public function itListsWhenEmpty(): void
    {
        // When
        $results = iterator_to_array($this->finder);

        // Then
        self::assertEmpty($results);
    }

    #[Test]
    public function itPaginates(): void
    {
        // Given
        $orders = OrderBuilder::new()->many(5)->create();
        $this->store(...$orders);

        // When
        $firstPage = $this->finder->paginate(page: 1, itemsPerPage: 2);
        $secondPage = $this->finder->paginate(page: 2, itemsPerPage: 2);
        $lastPage = $this->finder->paginate(page: 3, itemsPerPage: 2);
        $outOfBoundsPage = $this->finder->paginate(page: 4, itemsPerPage: 2);

        // Then
        self::assertSame($this->orderIds($orders[0], $orders[1]), $this->resultIds($firstPage));
        self::assertSame($this->orderIds($orders[2], $orders[3]), $this->resultIds($secondPage));
        self::assertSame($this->orderIds($orders[4]), $this->resultIds($lastPage));
        self::assertCount(0, $outOfBoundsPage);

        self::assertSame(5, $firstPage->totalItems());
        self::assertSame(3, $firstPage->lastPage());
        self::assertSame(1, $firstPage->currentPage());
        self::assertSame(2, $firstPage->itemsPerPage());
        self::assertSame(2, $secondPage->currentPage());
        self::assertSame(3, $lastPage->currentPage());
        self::assertSame(4, $outOfBoundsPage->currentPage());
    }

    #[Test]
    public function itPaginatesWhenEmpty(): void
    {
        // When
        $paginator = $this->finder->paginate(page: 1, itemsPerPage: 20);

        // Then
        self::assertCount(0, $paginator);
        self::assertSame(0, $paginator->totalItems());
        self::assertSame(1, $paginator->lastPage());
    }

    /**
     * @return list<string>
     */
    private function orderIds(Order ...$orders): array
    {
        $ids = [];
        foreach ($orders as $order) {
            $ids[] = $order->id->toString();
        }

        return $ids;
    }

    /**
     * @param iterable<OrderSummaryResult> $results
     *
     * @return list<string>
     */
    private function resultIds(iterable $results): array
    {
        $ids = [];
        foreach ($results as $result) {
            $ids[] = $result->orderId;
        }

        return $ids;
    }
}
