<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Policy;

use AfterSales\Return\Application\IntegrationEvent\WithdrawalRequested\WithdrawalRequestedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\OrderStatus;
use Sales\Order\Application\Policy\RequestOrderReturnOnWithdrawalRequested;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class RequestOrderReturnOnWithdrawalRequestedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRequestsReturn(): void
    {
        // Given
        $order = OrderBuilder::new()->confirmed()->prepared()->dispatched()->delivered()->create();
        $this->store($order);

        // When
        $this->trigger(RequestOrderReturnOnWithdrawalRequested::class, new WithdrawalRequestedIntegrationEvent(
            Uuid::uuid7()->toString(),
            $order->id->toString(),
            $order->buyerId,
            $order->shippingAddress->toArray(),
            Clock::get()->now(),
        ));

        // Then
        $result = $this->service(OrderFinderInterface::class)->ofId($order->id->toString());
        self::assertSame(OrderStatus::RETURN_REQUESTED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenNotFound(): void
    {
        // Given
        $order = OrderBuilder::new()->confirmed()->prepared()->dispatched()->delivered()->create();

        // When
        $this->trigger(RequestOrderReturnOnWithdrawalRequested::class, new WithdrawalRequestedIntegrationEvent(
            Uuid::uuid7()->toString(),
            $order->id->toString(),
            $order->buyerId,
            $order->shippingAddress->toArray(),
            Clock::get()->now(),
        ));

        // Then
        self::expectNotToPerformAssertions();
    }
}
