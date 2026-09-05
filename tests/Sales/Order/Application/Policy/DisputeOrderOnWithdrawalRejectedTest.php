<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Policy;

use AfterSales\Return\Application\IntegrationEvent\WithdrawalRejected\WithdrawalRejectedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\OrderStatus;
use Sales\Order\Application\Policy\DisputeOrderOnWithdrawalRejected;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class DisputeOrderOnWithdrawalRejectedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itDisputes(): void
    {
        // Given
        $order = OrderBuilder::new()->confirmed()->prepared()->dispatched()->delivered()->returnRequested()->create();
        $this->store($order);

        // When
        $this->trigger(DisputeOrderOnWithdrawalRejected::class, new WithdrawalRejectedIntegrationEvent(
            Uuid::uuid7()->toString(),
            $order->id->toString(),
            'Item damaged beyond resale',
            Clock::get()->now(),
        ));

        // Then
        $result = $this->service(OrderFinderInterface::class)->ofId($order->id->toString());
        self::assertSame(OrderStatus::DISPUTED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenNotFound(): void
    {
        // When
        $this->trigger(DisputeOrderOnWithdrawalRejected::class, new WithdrawalRejectedIntegrationEvent(
            Uuid::uuid7()->toString(),
            Uuid::uuid7()->toString(),
            'Item damaged beyond resale',
            Clock::get()->now(),
        ));

        // Then
        self::expectNotToPerformAssertions();
    }
}
