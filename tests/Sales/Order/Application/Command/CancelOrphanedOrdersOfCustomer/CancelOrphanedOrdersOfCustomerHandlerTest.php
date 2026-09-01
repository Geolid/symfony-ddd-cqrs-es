<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\CancelOrphanedOrdersOfCustomer;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Command\CancelOrphanedOrdersOfCustomer\CancelOrphanedOrdersOfCustomer;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\OrderStatus;
use Sales\Tests\Order\Support\Builder\OrderPaymentBuilder;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\AbstractIntegrationTestCase;

final class CancelOrphanedOrdersOfCustomerHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itCancelsWhenCancellable(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $alreadyCancelled = OrderBuilder::new()->withCustomerId($customerId)->cancelled()->create();
        $withCapturedPayment = OrderBuilder::new()->withCustomerId($customerId)->create();
        $orderPayment = OrderPaymentBuilder::new()->withOrderId($withCapturedPayment->id->toString())->authorized()->captured()->create();
        $placed = OrderBuilder::new()->withCustomerId($customerId)->create();
        $otherCustomerId = Uuid::uuid7()->toString();
        $other = OrderBuilder::new()->withCustomerId($otherCustomerId)->create();
        $this->store($alreadyCancelled, $withCapturedPayment, $orderPayment, $placed, $other);

        // When
        $this->dispatch(new CancelOrphanedOrdersOfCustomer($customerId));

        // Then
        $finder = $this->service(OrderFinderInterface::class);
        $statusesById = [];
        foreach ($finder->byCustomer($customerId) as $result) {
            $statusesById[$result->id] = $result->status;
        }
        self::assertSame(OrderStatus::CANCELLED, $statusesById[$placed->id->toString()]);
        self::assertSame(OrderStatus::CANCELLED, $statusesById[$alreadyCancelled->id->toString()]);
        self::assertSame(OrderStatus::CANCELLED, $statusesById[$withCapturedPayment->id->toString()]);

        $otherResults = iterator_to_array($finder->byCustomer($otherCustomerId), false);
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
        $results = iterator_to_array($this->service(OrderFinderInterface::class)->byCustomer($otherCustomerId), false);
        self::assertSame(OrderStatus::PLACED, $results[0]->status);
    }
}
