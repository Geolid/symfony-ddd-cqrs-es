<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\CancelOrder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Command\CancelOrder\CancelOrder;
use Sales\Order\Application\Enum\AppOrderStatus;
use Sales\Order\Application\Exception\OrderPaymentAlreadyCapturedException;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Domain\Exception\OrderAlreadyCancelledException;
use Sales\Order\Domain\Exception\OrderBelongsToAnotherCustomerException;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class CancelOrderHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itCancelsAPlacedOrder(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()->withCustomerId($customerId)->create();
        $this->store($order);

        // When
        $this->dispatch(new CancelOrder($order->id()->toString(), $customerId));

        // Then
        $results = array_values(iterator_to_array($this->service(OrderFinderInterface::class)));
        self::assertCount(1, $results);
        self::assertSame(AppOrderStatus::CANCELLED, $results[0]->status);
        self::assertNotNull($results[0]->cancelledAt);
    }

    #[Test]
    public function itFailsWhenTheOrderIsAlreadyCancelled(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()->withCustomerId($customerId)->cancelled()->create();
        $this->store($order);

        // Then
        $this->expectException(OrderAlreadyCancelledException::class);

        // When
        $this->dispatch(new CancelOrder($order->id()->toString(), $customerId));
    }

    #[Test]
    public function itFailsWhenTheOrderDoesNotExist(): void
    {
        // Then
        $this->expectException(OrderNotFoundException::class);

        // When
        $this->dispatch(new CancelOrder(OrderId::generate()->toString(), Uuid::uuid7()->toString()));
    }

    #[Test]
    public function itFailsWhenTheOrderBelongsToAnotherCustomer(): void
    {
        // Given
        $order = OrderTestFactory::new()->create();
        $this->store($order);

        // Then
        $this->expectException(OrderBelongsToAnotherCustomerException::class);

        // When
        $this->dispatch(new CancelOrder($order->id()->toString(), Uuid::uuid7()->toString()));
    }

    #[Test]
    public function itCancelsAnOrderWithPaymentRequestedButNotYetCaptured(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()->withCustomerId($customerId)->create();
        $this->store($order);
        $this->store(OrderPaymentTestFactory::new()->withOrderId($order->id()->toString())->create());

        // When
        $this->dispatch(new CancelOrder($order->id()->toString(), $customerId));

        // Then
        $results = array_values(iterator_to_array($this->service(OrderFinderInterface::class)));
        self::assertCount(1, $results);
        self::assertSame(AppOrderStatus::CANCELLED, $results[0]->status);
    }

    #[Test]
    public function itFailsWhenThePaymentHasAlreadyBeenCaptured(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()->withCustomerId($customerId)->create();
        $this->store($order);
        $this->store(OrderPaymentTestFactory::new()->withOrderId($order->id()->toString())->captured()->create());

        // Then
        $this->expectException(OrderPaymentAlreadyCapturedException::class);

        // When
        $this->dispatch(new CancelOrder($order->id()->toString(), $customerId));
    }
}
