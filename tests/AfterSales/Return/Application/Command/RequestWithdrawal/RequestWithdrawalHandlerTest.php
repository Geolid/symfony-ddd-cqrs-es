<?php

declare(strict_types=1);

namespace AfterSales\Tests\Return\Application\Command\RequestWithdrawal;

use AfterSales\Return\Application\Command\RequestWithdrawal\RequestWithdrawal;
use AfterSales\Return\Application\Exception\OrderResultNotFoundException;
use AfterSales\Return\Domain\Exception\CannotRequestWithdrawalForAnotherCustomerException;
use AfterSales\Return\Domain\Exception\WithdrawalWindowExpiredException;
use AfterSales\Return\Domain\Repository\WithdrawalRepositoryInterface;
use AfterSales\Return\Domain\ValueObject\WithdrawalId;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class RequestWithdrawalHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRequests(): void
    {
        // Given
        $builder = OrderBuilder::new()->confirmed()->dispatched()->delivered();
        $order = $builder->create();
        $this->store($order);

        // When
        $this->dispatch(new RequestWithdrawal($order->id->toString(), $builder['customerId']));

        // Then
        $withdrawal = $this->service(WithdrawalRepositoryInterface::class)->load(WithdrawalId::forOrder($order->id->toString()));
        $shippingAddress = $withdrawal->shippingAddress->toArray();
        $expectedShippingAddress = $builder['shippingAddress']->toArray();
        self::assertSame($order->id->toString(), $withdrawal->orderId);
        self::assertSame($builder['customerId'], $withdrawal->customerId);
        self::assertSame($expectedShippingAddress, $shippingAddress);
    }

    #[Test]
    public function itIgnoresWhenAlreadyRequested(): void
    {
        // Given
        $builder = OrderBuilder::new()->confirmed()->dispatched()->delivered();
        $order = $builder->create();
        $this->store($order);
        $this->dispatch(new RequestWithdrawal($order->id->toString(), $builder['customerId']));

        // When
        $this->dispatch(new RequestWithdrawal($order->id->toString(), $builder['customerId']));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();

        // Then
        $this->expectException(OrderResultNotFoundException::class);

        // When
        $this->dispatch(new RequestWithdrawal($orderId, Uuid::uuid7()->toString()));
    }

    #[Test]
    public function itFailsWhenBelongingToAnotherCustomer(): void
    {
        // Given
        $order = OrderBuilder::new()->confirmed()->dispatched()->delivered()->create();
        $this->store($order);

        // Then
        $this->expectException(CannotRequestWithdrawalForAnotherCustomerException::class);

        // When
        $this->dispatch(new RequestWithdrawal($order->id->toString(), Uuid::uuid7()->toString()));
    }

    #[Test]
    public function itFailsWhenWindowExpired(): void
    {
        // Given
        $builder = OrderBuilder::new()->confirmed()->dispatched()->delivered(Clock::get()->now()->modify('-15 days'));
        $order = $builder->create();
        $this->store($order);

        // Then
        $this->expectException(WithdrawalWindowExpiredException::class);

        // When
        $this->dispatch(new RequestWithdrawal($order->id->toString(), $builder['customerId']));
    }
}
