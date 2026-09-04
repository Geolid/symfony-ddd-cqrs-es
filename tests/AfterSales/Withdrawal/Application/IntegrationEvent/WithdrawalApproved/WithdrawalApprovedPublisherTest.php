<?php

declare(strict_types=1);

namespace AfterSales\Tests\Withdrawal\Application\IntegrationEvent\WithdrawalApproved;

use AfterSales\Tests\Withdrawal\Support\Builder\WithdrawalBuilder;
use AfterSales\Withdrawal\Application\IntegrationEvent\WithdrawalApproved\WithdrawalApprovedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class WithdrawalApprovedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = WithdrawalBuilder::new()->received()->approved();
        $withdrawal = $builder->create();

        // When
        $this->store($withdrawal);

        // Then
        $event = $this->publishedEventOf(WithdrawalApprovedIntegrationEvent::class);
        self::assertSame($withdrawal->id->toString(), $event->withdrawalId);
        self::assertSame($builder['orderId'], $event->orderId);
        self::assertSame($builder['approvedAt']->format(\DateTimeInterface::ATOM), $event->approvedAt->format(\DateTimeInterface::ATOM));
    }
}
