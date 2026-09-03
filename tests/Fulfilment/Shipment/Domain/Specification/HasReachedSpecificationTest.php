<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Domain\Specification;

use Fulfilment\Shipment\Domain\Specification\HasReachedSpecification;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentState;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class HasReachedSpecificationTest extends TestCase
{
    /** @var array<string, list<ShipmentState>> */
    private const array TRANSITIONS = [
        ShipmentState::REQUESTED->value => [ShipmentState::PREPARED, ShipmentState::CANCELLED],
        ShipmentState::PREPARED->value => [ShipmentState::MANIFESTED],
        ShipmentState::MANIFESTED->value => [ShipmentState::DISPATCHED],
        ShipmentState::DISPATCHED->value => [ShipmentState::DELIVERED],
        ShipmentState::DELIVERED->value => [],
        ShipmentState::CANCELLED->value => [],
    ];

    #[Test]
    #[DataProvider('provideTransitions')]
    public function itIsSatisfiedBy(ShipmentState $candidate, ShipmentState $target, bool $expected): void
    {
        // Given
        $specification = new HasReachedSpecification(self::TRANSITIONS, $target);

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
        yield 'same state' => [ShipmentState::PREPARED, ShipmentState::PREPARED, true];
        yield 'one hop away' => [ShipmentState::MANIFESTED, ShipmentState::PREPARED, true];
        yield 'several hops away' => [ShipmentState::DELIVERED, ShipmentState::PREPARED, true];
        yield 'backward' => [ShipmentState::MANIFESTED, ShipmentState::DISPATCHED, false];
        yield 'other branch' => [ShipmentState::CANCELLED, ShipmentState::PREPARED, false];
        yield 'dead end itself' => [ShipmentState::CANCELLED, ShipmentState::CANCELLED, true];
        yield 'dead end' => [ShipmentState::PREPARED, ShipmentState::CANCELLED, false];
    }
}
