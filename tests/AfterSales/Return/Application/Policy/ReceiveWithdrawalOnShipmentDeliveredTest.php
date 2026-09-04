<?php

declare(strict_types=1);

namespace AfterSales\Tests\Return\Application\Policy;

use AfterSales\Return\Application\Policy\ReceiveWithdrawalOnShipmentDelivered;
use AfterSales\Return\Domain\Event\WithdrawalReceived;
use AfterSales\Tests\Return\Support\Builder\WithdrawalBuilder;
use Fulfilment\Shipment\Application\IntegrationEvent\ShipmentDelivered\ShipmentDeliveredIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ReceiveWithdrawalOnShipmentDeliveredTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itReceives(): void
    {
        // Given
        $withdrawal = WithdrawalBuilder::new()->create();
        $this->store($withdrawal);

        // When
        $this->trigger(
            ReceiveWithdrawalOnShipmentDelivered::class,
            new ShipmentDeliveredIntegrationEvent(Uuid::uuid7()->toString(), $withdrawal->id->toString(), Clock::get()->now()),
        );

        // Then
        $event = $this->publishedEventOf(WithdrawalReceived::class);
        self::assertSame($withdrawal->id->toString(), $event->id);
    }

    #[Test]
    public function itIgnoresWhenReferenceUnrelated(): void
    {
        // When
        $this->trigger(
            ReceiveWithdrawalOnShipmentDelivered::class,
            new ShipmentDeliveredIntegrationEvent(Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), Clock::get()->now()),
        );

        // Then
        self::expectNotToPerformAssertions();
    }
}
