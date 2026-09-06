<?php

declare(strict_types=1);

namespace Finance\Tests\Refund\Application\IntegrationEvent\RefundInitiated;

use Finance\Refund\Application\IntegrationEvent\RefundInitiated\RefundInitiatedIntegrationEvent;
use Finance\Tests\Refund\Support\Builder\RefundBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class RefundInitiatedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = RefundBuilder::new();
        $refund = $builder->create();

        // When
        $this->store($refund);

        // Then
        $event = $this->publishedEventOf(RefundInitiatedIntegrationEvent::class);
        self::assertSame($refund->id->toString(), $event->refundId);
        self::assertSame($builder['paymentId'], $event->paymentId);
        self::assertSame($builder['orderId'], $event->orderId);
        self::assertSame($builder['amount']->cents, $event->amountInCents);
    }
}
