<?php

declare(strict_types=1);

namespace AfterSales\Tests\Withdrawal\Application\IntegrationEvent\WithdrawalRequested;

use AfterSales\Tests\Withdrawal\Support\Builder\WithdrawalBuilder;
use AfterSales\Withdrawal\Application\IntegrationEvent\WithdrawalRequested\WithdrawalRequestedIntegrationEvent;
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
        $shippingAddress = $builder['shippingAddress']->toArray();
        self::assertSame($withdrawal->id->toString(), $event->withdrawalId);
        self::assertSame($builder['orderId'], $event->orderId);
        self::assertSame($builder['customerId'], $event->customerId);
        self::assertSame($shippingAddress, $event->shippingAddress);
        self::assertSame($builder['requestedAt']->format(\DateTimeInterface::ATOM), $event->requestedAt->format(\DateTimeInterface::ATOM));
    }
}
