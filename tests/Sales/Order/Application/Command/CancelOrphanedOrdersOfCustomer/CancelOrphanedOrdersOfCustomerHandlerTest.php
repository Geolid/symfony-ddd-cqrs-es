<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\CancelOrphanedOrdersOfCustomer;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Command\CancelOrphanedOrdersOfCustomer\CancelOrphanedOrdersOfCustomer;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\OrderStatus;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Sales\Tests\Order\Support\Builder\OrderPaymentBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class CancelOrphanedOrdersOfCustomerHandlerTest extends AbstractIntegrationTestCase
{
    private OrderFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(OrderFinderInterface::class);
    }

    #[Test]
    public function itCancelsWhenCancellable(): void
    {
        // Given
        $otherCustomerId = Uuid::uuid7()->toString();
        $other = OrderBuilder::new()->withCustomerId($otherCustomerId)->create();

        $customerId = Uuid::uuid7()->toString();
        $alreadyCancelled = OrderBuilder::new()->withCustomerId($customerId)->cancelled()->create();
        $withCapturedPayment = OrderBuilder::new()->withCustomerId($customerId)->create();
        $orderPayment = OrderPaymentBuilder::new()->withOrderId($withCapturedPayment->id->toString())->authorized()->captured()->create();
        $placed = OrderBuilder::new()->withCustomerId($customerId)->create();
        $this->store($other, $alreadyCancelled, $withCapturedPayment, $orderPayment, $placed);

        // When
        $this->dispatch(new CancelOrphanedOrdersOfCustomer($customerId));

        // Then
        $statusesById = [];
        foreach ($this->finder->byCustomer($customerId) as $result) {
            $statusesById[$result->id] = $result->status;
        }
        self::assertSame(OrderStatus::CANCELLED, $statusesById[$placed->id->toString()]);
        self::assertSame(OrderStatus::CANCELLED, $statusesById[$alreadyCancelled->id->toString()]);
        self::assertSame(OrderStatus::CANCELLED, $statusesById[$withCapturedPayment->id->toString()]);

        $otherResults = iterator_to_array($this->finder->byCustomer($otherCustomerId), false);
        self::assertSame(OrderStatus::PLACED, $otherResults[0]->status);
    }

    #[Test]
    public function itIgnoresWhenNoneExist(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $otherCustomerId = Uuid::uuid7()->toString();
        $other = OrderBuilder::new()->withCustomerId($otherCustomerId)->create();
        $this->store($other);

        // When
        $this->dispatch(new CancelOrphanedOrdersOfCustomer($customerId));

        // Then
        $results = iterator_to_array($this->finder->byCustomer($otherCustomerId), false);
        self::assertSame(OrderStatus::PLACED, $results[0]->status);
    }
}
