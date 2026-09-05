<?php

declare(strict_types=1);

namespace AfterSales\Tests\Return\Application\Query\CanRequestWithdrawal;

use AfterSales\Return\Application\Query\CanRequestWithdrawal\CanRequestWithdrawal;
use AfterSales\Tests\Return\Support\Builder\WithdrawalBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class CanRequestWithdrawalHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itAccepts(): void
    {
        // Given
        $order = OrderBuilder::new()->confirmed()->prepared()->dispatched()->delivered()->create();
        $this->store($order);

        // When
        $accepted = $this->ask(new CanRequestWithdrawal($order->id->toString()));

        // Then
        self::assertTrue($accepted);
    }

    #[Test]
    public function itRefusesWhenNotFound(): void
    {
        // When
        $accepted = $this->ask(new CanRequestWithdrawal(Uuid::uuid7()->toString()));

        // Then
        self::assertFalse($accepted);
    }

    #[Test]
    public function itRefusesWhenActiveWithdrawalExists(): void
    {
        // Given
        $order = OrderBuilder::new()->confirmed()->prepared()->dispatched()->delivered()->create();
        $withdrawal = WithdrawalBuilder::new()->withOrderId($order->id->toString())->create();
        $this->store($order, $withdrawal);

        // When
        $accepted = $this->ask(new CanRequestWithdrawal($order->id->toString()));

        // Then
        self::assertFalse($accepted);
    }

    #[Test]
    public function itRefusesWhenWindowExpired(): void
    {
        // Given
        $order = OrderBuilder::new()->confirmed()->prepared()->dispatched()->delivered(Clock::get()->now()->modify('-15 days'))->create();
        $this->store($order);

        // When
        $accepted = $this->ask(new CanRequestWithdrawal($order->id->toString()));

        // Then
        self::assertFalse($accepted);
    }
}
