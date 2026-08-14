<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\CancelOrdersForCustomer;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Command\CancelOrdersForCustomer\CancelOrdersForCustomer;
use Sales\Order\Application\Enum\OrderStatus;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class CancelOrdersForCustomerHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itCancelsEveryCancellableOrderOfTheCustomer(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $alreadyCancelled = OrderTestFactory::new()->withCustomerId($customerId)->cancelled()->store();
        $paid = OrderTestFactory::new()->withCustomerId($customerId)->store();
        OrderPaymentTestFactory::new()->withOrderId($paid->id()->toString())->captured()->store();
        $placed = OrderTestFactory::new()->withCustomerId($customerId)->store();
        $otherCustomerId = Uuid::uuid7()->toString();
        OrderTestFactory::new()->withCustomerId($otherCustomerId)->store();

        // When
        $this->dispatch(new CancelOrdersForCustomer($customerId));

        // Then
        $finder = $this->service(OrderFinderInterface::class);
        $statusesById = [];
        foreach ($finder->byCustomer($customerId) as $result) {
            $statusesById[$result->id] = $result->status;
        }
        self::assertSame(OrderStatus::CANCELLED, $statusesById[$placed->id()->toString()]);
        self::assertSame(OrderStatus::CANCELLED, $statusesById[$alreadyCancelled->id()->toString()]);
        self::assertSame(OrderStatus::PLACED, $statusesById[$paid->id()->toString()]);

        $otherResults = iterator_to_array($finder->byCustomer($otherCustomerId), false);
        self::assertSame(OrderStatus::PLACED, $otherResults[0]->status);
    }

    #[Test]
    public function itIgnoresACustomerWithNoOrders(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $otherCustomerId = Uuid::uuid7()->toString();
        OrderTestFactory::new()->withCustomerId($otherCustomerId)->store();

        // When
        $this->dispatch(new CancelOrdersForCustomer($customerId));

        // Then
        $results = iterator_to_array($this->service(OrderFinderInterface::class)->byCustomer($otherCustomerId), false);
        self::assertSame(OrderStatus::PLACED, $results[0]->status);
    }
}
