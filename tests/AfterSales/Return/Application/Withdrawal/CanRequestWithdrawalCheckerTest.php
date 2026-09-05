<?php

declare(strict_types=1);

namespace AfterSales\Tests\Return\Application\Withdrawal;

use AfterSales\Return\Application\Withdrawal\CanRequestWithdrawalInterface;
use AfterSales\Tests\Return\Support\Builder\WithdrawalBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class CanRequestWithdrawalCheckerTest extends AbstractIntegrationTestCase
{
    private CanRequestWithdrawalInterface $checker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->checker = $this->service(CanRequestWithdrawalInterface::class);
    }

    #[Test]
    public function itAccepts(): void
    {
        // Given
        $order = OrderBuilder::new()->confirmed()->prepared()->dispatched()->delivered()->create();
        $this->store($order);

        // When
        $accepted = $this->checker->forOrder($order->id->toString());

        // Then
        self::assertTrue($accepted);
    }

    #[Test]
    public function itRefusesWhenNotFound(): void
    {
        // When
        $accepted = $this->checker->forOrder(Uuid::uuid7()->toString());

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
        $accepted = $this->checker->forOrder($order->id->toString());

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
        $accepted = $this->checker->forOrder($order->id->toString());

        // Then
        self::assertFalse($accepted);
    }

    #[Test]
    public function itChecksBatch(): void
    {
        // Given
        $eligible = OrderBuilder::new()->confirmed()->prepared()->dispatched()->delivered()->create();
        $ineligible = OrderBuilder::new()->confirmed()->prepared()->dispatched()->delivered(Clock::get()->now()->modify('-15 days'))->create();
        $this->store($eligible, $ineligible);

        // When
        $results = $this->checker->forOrders($eligible->id->toString(), $ineligible->id->toString());

        // Then
        self::assertTrue($results[$eligible->id->toString()]);
        self::assertFalse($results[$ineligible->id->toString()]);
    }
}
