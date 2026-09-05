<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Policy;

use AfterSales\Return\Application\IntegrationEvent\WithdrawalApproved\WithdrawalApprovedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\OrderStatus;
use Sales\Order\Application\Policy\ReturnOrderOnWithdrawalApproved;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ReturnOrderOnWithdrawalApprovedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itReturns(): void
    {
        // Given
        $order = OrderBuilder::new()->confirmed()->prepared()->dispatched()->delivered()->returnRequested()->create();
        $this->store($order);

        // When
        $this->trigger(ReturnOrderOnWithdrawalApproved::class, new WithdrawalApprovedIntegrationEvent(Uuid::uuid7()->toString(), $order->id->toString(), Clock::get()->now()));

        // Then
        $result = $this->service(OrderFinderInterface::class)->ofId($order->id->toString());
        self::assertSame(OrderStatus::RETURNED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenNotFound(): void
    {
        // When
        $this->trigger(ReturnOrderOnWithdrawalApproved::class, new WithdrawalApprovedIntegrationEvent(Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), Clock::get()->now()));

        // Then
        self::expectNotToPerformAssertions();
    }
}
