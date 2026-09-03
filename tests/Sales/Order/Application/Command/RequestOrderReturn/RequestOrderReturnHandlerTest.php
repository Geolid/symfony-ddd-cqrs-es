<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\RequestOrderReturn;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Command\RequestOrderReturn\RequestOrderReturn;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\OrderStatus;
use Sales\Order\Domain\Exception\OrderBelongsToAnotherCustomerException;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\Exception\OrderNotReturnableException;
use Sales\Order\Domain\Exception\OrderReturnWindowExpiredException;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class RequestOrderReturnHandlerTest extends AbstractIntegrationTestCase
{
    private OrderFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(OrderFinderInterface::class);
    }

    #[Test]
    public function itRequestsReturnWhenDelivered(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderBuilder::new()->withCustomerId($customerId)->confirmed()->dispatched()->delivered()->create();
        $this->store($order);

        // When
        $this->dispatch(new RequestOrderReturn($order->id->toString(), $customerId));

        // Then
        $result = $this->finder->ofId($order->id->toString());
        self::assertSame(OrderStatus::RETURN_REQUESTED, $result->status);
        self::assertNotNull($result->returnRequestedAt);
    }

    #[Test]
    public function itIgnoresWhenAlreadyRequested(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderBuilder::new()->withCustomerId($customerId)->confirmed()->dispatched()->delivered()->returnRequested()->create();
        $this->store($order);

        // When
        $this->dispatch(new RequestOrderReturn($order->id->toString(), $customerId));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = OrderId::generate()->toString();

        // Then
        $this->expectException(OrderNotFoundException::class);

        // When
        $this->dispatch(new RequestOrderReturn($id, Uuid::uuid7()->toString()));
    }

    #[Test]
    public function itFailsWhenBelongsToAnotherCustomer(): void
    {
        // Given
        $order = OrderBuilder::new()->confirmed()->dispatched()->delivered()->create();
        $this->store($order);

        // Then
        $this->expectException(OrderBelongsToAnotherCustomerException::class);

        // When
        $this->dispatch(new RequestOrderReturn($order->id->toString(), Uuid::uuid7()->toString()));
    }

    #[Test]
    public function itFailsWhenNotReturnable(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderBuilder::new()->withCustomerId($customerId)->create();
        $this->store($order);

        // Then
        $this->expectException(OrderNotReturnableException::class);

        // When
        $this->dispatch(new RequestOrderReturn($order->id->toString(), $customerId));
    }

    #[Test]
    public function itFailsWhenReturnWindowHasExpired(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderBuilder::new()
            ->withCustomerId($customerId)
            ->confirmed()
            ->dispatched()
            ->delivered(Clock::get()->now()->modify('-30 days'))
            ->create();
        $this->store($order);

        // Then
        $this->expectException(OrderReturnWindowExpiredException::class);

        // When
        $this->dispatch(new RequestOrderReturn($order->id->toString(), $customerId));
    }
}
