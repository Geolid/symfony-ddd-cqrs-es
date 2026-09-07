<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasure\Application\Policy;

use Compliance\Erasure\Application\Command\PlaceSubjectErasureHold\PlaceSubjectErasureHold;
use Compliance\Erasure\Application\Policy\PlaceSubjectErasureHoldOnOrderPlaced;
use Compliance\Erasure\Domain\ValueObject\SubjectId;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\IntegrationEvent\OrderPlaced\OrderPlacedIntegrationEvent;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Command\CommandInterface;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class PlaceSubjectErasureHoldOnOrderPlacedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPlaces(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $identityId = Uuid::uuid7()->toString();
        $buyer = BuyerBuilder::new()->withIdentityId($identityId)->create();

        $dispatched = null;
        $commandBus = $this->createMock(CommandBusInterface::class);
        $this->replace(CommandBusInterface::class, $commandBus);
        $commandBus->expects(self::once())->method('dispatch')
            ->willReturnCallback(static function (CommandInterface $command) use (&$dispatched): void {
                $dispatched = $command;
            });

        $this->store($buyer);

        // When
        $this->trigger(PlaceSubjectErasureHoldOnOrderPlaced::class, new OrderPlacedIntegrationEvent(
            orderId: $orderId,
            buyerId: $buyer->id->toString(),
            lines: [],
            totalAmountInCents: 1_000,
            billingAddress: OrderBuilder::sample('billingAddress')->toArray(),
            placedAt: Clock::get()->now(),
        ));

        // Then
        self::assertInstanceOf(PlaceSubjectErasureHold::class, $dispatched);
        self::assertSame(SubjectId::forIdentity($identityId)->toString(), $dispatched->subjectId);
        self::assertSame('sales.order.order', $dispatched->sourceType);
        self::assertSame($orderId, $dispatched->sourceId);
    }
}
