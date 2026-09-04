<?php

declare(strict_types=1);

namespace AfterSales\Tests\Return\Application\IntegrationEvent\WithdrawalRejected;

use AfterSales\Return\Application\IntegrationEvent\WithdrawalRejected\WithdrawalRejectedIntegrationEvent;
use AfterSales\Tests\Return\Support\Builder\WithdrawalBuilder;
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
