<?php

declare(strict_types=1);

namespace AfterSales\Tests\Return\Application\Command\RequestWithdrawal;

use AfterSales\Return\Application\Command\RequestWithdrawal\RequestWithdrawal;
use AfterSales\Return\Application\Exception\ActiveWithdrawalAlreadyExistsException;
use AfterSales\Return\Application\Exception\DeliveredOrderResultNotFoundException;
use AfterSales\Return\Application\Finder\Withdrawal\WithdrawalFinderInterface;
use AfterSales\Return\Domain\Exception\CannotRequestWithdrawalForAnotherBuyerException;
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
        $builder = OrderBuilder::new()->confirmed()->prepared()->dispatched()->delivered();
        $order = $builder->create();
        $this->store($order);

        // When
        $this->dispatch(new RequestWithdrawal($order->id->toString(), $builder['buyerId']));

        // Then
        $result = iterator_to_array($this->service(WithdrawalFinderInterface::class)->byOrder($order->id->toString()))[0];
        $withdrawal = $this->service(WithdrawalRepositoryInterface::class)->load(WithdrawalId::fromString($result->id));
        self::assertSame($order->id->toString(), $withdrawal->orderId);
        self::assertSame($builder['buyerId'], $withdrawal->buyerId);
        self::assertSame($builder['shippingAddress']->toArray(), $withdrawal->shippingAddress->toArray());
    }

    #[Test]
    public function itFailsWhenAlreadyRequested(): void
    {
        // Given
        $builder = OrderBuilder::new()->confirmed()->prepared()->dispatched()->delivered();
        $order = $builder->create();
        $this->store($order);
        $this->dispatch(new RequestWithdrawal($order->id->toString(), $builder['buyerId']));

        // Then
        $this->expectException(ActiveWithdrawalAlreadyExistsException::class);

        // When
        $this->dispatch(new RequestWithdrawal($order->id->toString(), $builder['buyerId']));
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();

        // Then
        $this->expectException(DeliveredOrderResultNotFoundException::class);

        // When
        $this->dispatch(new RequestWithdrawal($orderId, Uuid::uuid7()->toString()));
    }

    #[Test]
    public function itFailsWhenBelongingToAnotherBuyer(): void
    {
        // Given
        $order = OrderBuilder::new()->confirmed()->prepared()->dispatched()->delivered()->create();
        $this->store($order);

        // Then
        $this->expectException(CannotRequestWithdrawalForAnotherBuyerException::class);

        // When
        $this->dispatch(new RequestWithdrawal($order->id->toString(), Uuid::uuid7()->toString()));
    }

    #[Test]
    public function itFailsWhenWindowExpired(): void
    {
        // Given
        $builder = OrderBuilder::new()->confirmed()->prepared()->dispatched()->delivered(Clock::get()->now()->modify('-15 days'));
        $order = $builder->create();
        $this->store($order);

        // Then
        $this->expectException(WithdrawalWindowExpiredException::class);

        // When
        $this->dispatch(new RequestWithdrawal($order->id->toString(), $builder['buyerId']));
    }
}
