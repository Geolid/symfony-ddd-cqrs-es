<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\CancelOrphanedOrdersOfCustomer;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Command\CancelOrphanedOrdersOfCustomer\CancelOrphanedOrdersOfCustomer;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Status\OrderStatus;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class CancelOrphanedOrdersOfCustomerHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itCancelsWhenCancellable(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $alreadyCancelled = OrderTestFactory::new()->withCustomerId($customerId)->cancelled()->store();
        $withCapturedPayment = OrderTestFactory::new()->withCustomerId($customerId)->store();
        OrderPaymentTestFactory::new()->withOrderId($withCapturedPayment->id->toString())->authorized()->captured()->store();
        $placed = OrderTestFactory::new()->withCustomerId($customerId)->store();
        $otherCustomerId = Uuid::uuid7()->toString();
        OrderTestFactory::new()->withCustomerId($otherCustomerId)->store();

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
        OrderTestFactory::new()->withCustomerId($otherCustomerId)->store();

        // When
        $this->dispatch(new CancelOrphanedOrdersOfCustomer($customerId));

        // Then
        $results = iterator_to_array($this->service(OrderFinderInterface::class)->byCustomer($otherCustomerId), false);
        self::assertSame(OrderStatus::PLACED, $results[0]->status);
    }
}
