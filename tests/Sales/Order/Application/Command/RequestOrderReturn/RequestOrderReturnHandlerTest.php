<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\RequestOrderReturn;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Command\RequestOrderReturn\RequestOrderReturn;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Status\OrderStatus;
use Sales\Order\Domain\Exception\OrderBelongsToAnotherCustomerException;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\Exception\OrderNotReturnableException;
use Sales\Order\Domain\Exception\OrderReturnWindowExpiredException;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class RequestOrderReturnHandlerTest extends AbstractIntegrationTestCase
{
    private OrderFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(OrderFinderInterface::class);
    }

    #[Test]
    public function itRequestsAReturnOnACompletedOrder(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()->withCustomerId($customerId)->confirmed()->dispatched()->completed()->store();

        // When
        $this->dispatch(new RequestOrderReturn($order->id()->toString(), $customerId));

        // Then
        $result = $this->finder->ofId($order->id()->toString());
        self::assertSame(OrderStatus::RETURN_REQUESTED, $result->status);
        self::assertNotNull($result->returnRequestedAt);
    }

    #[Test]
    public function itIgnoresAnAlreadyRequestedReturn(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()->withCustomerId($customerId)->confirmed()->dispatched()->completed()->returnRequested()->store();

        // When
        $this->dispatch(new RequestOrderReturn($order->id()->toString(), $customerId));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenTheOrderBelongsToAnotherCustomer(): void
    {
        // Given
        $order = OrderTestFactory::new()->confirmed()->dispatched()->completed()->store();

        // Then
        $this->expectException(OrderBelongsToAnotherCustomerException::class);

        // When
        $this->dispatch(new RequestOrderReturn($order->id()->toString(), Uuid::uuid7()->toString()));
    }

    #[Test]
    public function itFailsWhenTheOrderIsNotYetReturnable(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()->withCustomerId($customerId)->store();

        // Then
        $this->expectException(OrderNotReturnableException::class);

        // When
        $this->dispatch(new RequestOrderReturn($order->id()->toString(), $customerId));
    }

    #[Test]
    public function itFailsWhenTheReturnWindowHasExpired(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()
            ->withCustomerId($customerId)
            ->confirmed()
            ->dispatched()
            ->completed(new \DateTimeImmutable('2020-01-01T00:00:00+00:00'))
            ->store();

        // Then
        $this->expectException(OrderReturnWindowExpiredException::class);

        // When
        $this->dispatch(new RequestOrderReturn($order->id()->toString(), $customerId));
    }

    #[Test]
    public function itFailsWhenTheOrderDoesNotExist(): void
    {
        // Given
        $id = OrderId::generate()->toString();

        // Then
        $this->expectException(OrderNotFoundException::class);

        // When
        $this->dispatch(new RequestOrderReturn($id, Uuid::uuid7()->toString()));
    }
}
