<?php

declare(strict_types=1);

namespace AfterSales\Tests\Withdrawal\Application\IntegrationEvent\WithdrawalRejected;

use AfterSales\Tests\Withdrawal\Support\Builder\WithdrawalBuilder;
use AfterSales\Withdrawal\Application\IntegrationEvent\WithdrawalRejected\WithdrawalRejectedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class WithdrawalRejectedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = WithdrawalBuilder::new()->received()->rejected();
        $withdrawal = $builder->create();

        // When
        $this->store($withdrawal);

        // Then
        $event = $this->publishedEventOf(WithdrawalRejectedIntegrationEvent::class);
        self::assertSame($withdrawal->id->toString(), $event->withdrawalId);
        self::assertSame($builder['orderId'], $event->orderId);
        self::assertSame($builder['reason'], $event->reason);
        self::assertSame($builder['rejectedAt']->format(\DateTimeInterface::ATOM), $event->rejectedAt->format(\DateTimeInterface::ATOM));
    }
}
