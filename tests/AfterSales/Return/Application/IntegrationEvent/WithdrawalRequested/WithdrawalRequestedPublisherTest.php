<?php

declare(strict_types=1);

namespace AfterSales\Tests\Return\Application\IntegrationEvent\WithdrawalRequested;

use AfterSales\Return\Application\IntegrationEvent\WithdrawalRequested\WithdrawalRequestedIntegrationEvent;
use AfterSales\Tests\Return\Support\Builder\WithdrawalBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class WithdrawalRequestedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = WithdrawalBuilder::new();
        $withdrawal = $builder->create();

        // When
        $this->store($withdrawal);

        // Then
        $event = $this->publishedEventOf(WithdrawalRequestedIntegrationEvent::class);
        self::assertSame($withdrawal->id->toString(), $event->withdrawalId);
        self::assertSame($builder['orderId'], $event->orderId);
        self::assertSame($builder['buyerId'], $event->buyerId);
        self::assertSame($builder['shippingAddress']->toArray(), $event->shippingAddress);
        self::assertSame($builder['requestedAt']->format(\DateTimeInterface::ATOM), $event->requestedAt->format(\DateTimeInterface::ATOM));
    }
}
