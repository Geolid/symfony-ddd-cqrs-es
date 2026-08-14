<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\CancelOrder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Command\CancelOrder\CancelOrder;
use Sales\Order\Application\Enum\OrderStatus;
use Sales\Order\Application\Exception\OrderPaymentAlreadyCapturedException;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Domain\Exception\OrderBelongsToAnotherCustomerException;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Shared\Domain\Exception\AggregateNotFoundException;
use Support\AbstractIntegrationTestCase;

final class CancelOrderHandlerTest extends AbstractIntegrationTestCase
{
    private OrderFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(OrderFinderInterface::class);
    }

    #[Test]
    public function itCancelsAPlacedOrder(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()->withCustomerId($customerId)->store();

        // When
        $this->dispatch(new CancelOrder($order->id()->toString(), $customerId));

        // Then
        $result = $this->finder->ofId($order->id()->toString());
        self::assertSame(OrderStatus::CANCELLED, $result->status);
        self::assertNotNull($result->cancelledAt);
    }

    #[Test]
    public function itIgnoresAnAlreadyCancelledOrder(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()->withCustomerId($customerId)->cancelled()->store();

        // When
        $this->dispatch(new CancelOrder($order->id()->toString(), $customerId));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenTheOrderDoesNotExist(): void
    {
        // Given
        $id = OrderId::generate()->toString();
        $customerId = Uuid::uuid7()->toString();

        // Then
        $this->expectException(AggregateNotFoundException::class);

        // When
        $this->dispatch(new CancelOrder($id, $customerId));
    }

    #[Test]
    public function itFailsWhenTheOrderBelongsToAnotherCustomer(): void
    {
        // Given
        $order = OrderTestFactory::new()->store();

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
        $order = OrderTestFactory::new()->withCustomerId($customerId)->store();
        OrderPaymentTestFactory::new()->withOrderId($order->id()->toString())->store();

        // When
        $this->dispatch(new CancelOrder($order->id()->toString(), $customerId));

        // Then
        $result = $this->finder->ofId($order->id()->toString());
        self::assertSame(OrderStatus::CANCELLED, $result->status);
    }

    #[Test]
    public function itFailsWhenThePaymentHasAlreadyBeenCaptured(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()->withCustomerId($customerId)->store();
        OrderPaymentTestFactory::new()->withOrderId($order->id()->toString())->captured()->store();

        // Then
        $this->expectException(OrderPaymentAlreadyCapturedException::class);

        // When
        $this->dispatch(new CancelOrder($order->id()->toString(), $customerId));
    }
}
