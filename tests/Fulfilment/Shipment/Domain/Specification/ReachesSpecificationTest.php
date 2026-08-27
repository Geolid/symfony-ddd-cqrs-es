<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Domain\Specification;

use Fulfilment\Shipment\Domain\Specification\ReachesSpecification;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentState;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ReachesSpecificationTest extends TestCase
{
    /** @var array<string, list<ShipmentState>> */
    private const array TRANSITIONS = [
        'requested' => [ShipmentState::PREPARED, ShipmentState::CANCELLED],
        'prepared' => [ShipmentState::MANIFESTED],
        'manifested' => [ShipmentState::DISPATCHED],
        'dispatched' => [ShipmentState::DELIVERED],
        'delivered' => [],
        'cancelled' => [],
    ];

    #[Test]
    #[DataProvider('provideTransitions')]
    public function itIsSatisfiedBy(ShipmentState $candidate, ShipmentState $target, bool $expected): void
    {
        // Given
        $specification = new ReachesSpecification(self::TRANSITIONS, $target);

        // When
        $result = $specification->isSatisfiedBy($candidate);

        // Then
        self::assertSame($expected, $result);
    }

    /**
     * @return iterable<string, array{ShipmentState, ShipmentState, bool}>
     */
    public static function provideTransitions(): iterable
    {
        yield 'reaches itself' => [ShipmentState::PREPARED, ShipmentState::PREPARED, true];
        yield 'reaches one hop away' => [ShipmentState::MANIFESTED, ShipmentState::PREPARED, true];
        yield 'reaches several hops away' => [ShipmentState::DELIVERED, ShipmentState::PREPARED, true];
        yield 'does not reach backward' => [ShipmentState::MANIFESTED, ShipmentState::DISPATCHED, false];
        yield 'does not cross into the other branch' => [ShipmentState::CANCELLED, ShipmentState::PREPARED, false];
        yield 'a dead end reaches itself' => [ShipmentState::CANCELLED, ShipmentState::CANCELLED, true];
        yield 'a dead end reaches nothing else' => [ShipmentState::PREPARED, ShipmentState::CANCELLED, false];
    }
}
