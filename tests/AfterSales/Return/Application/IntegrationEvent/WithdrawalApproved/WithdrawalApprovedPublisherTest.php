<?php

declare(strict_types=1);

namespace AfterSales\Tests\Return\Application\IntegrationEvent\WithdrawalApproved;

use AfterSales\Return\Application\IntegrationEvent\WithdrawalApproved\WithdrawalApprovedIntegrationEvent;
use AfterSales\Tests\Return\Support\Builder\WithdrawalBuilder;
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
