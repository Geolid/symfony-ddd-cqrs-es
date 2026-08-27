<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Domain\Specification;

use Fulfilment\Shipment\Domain\Specification\CanTransitionToSpecification;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentState;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CanTransitionToSpecificationTest extends TestCase
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
        $specification = new CanTransitionToSpecification(self::TRANSITIONS, $target);

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
        yield 'direct hop' => [ShipmentState::PREPARED, ShipmentState::MANIFESTED, true];
        yield 'one of two branches' => [ShipmentState::REQUESTED, ShipmentState::PREPARED, true];
        yield 'the other branch' => [ShipmentState::REQUESTED, ShipmentState::CANCELLED, true];
        yield 'two hops away' => [ShipmentState::REQUESTED, ShipmentState::MANIFESTED, false];
        yield 'backward' => [ShipmentState::MANIFESTED, ShipmentState::PREPARED, false];
        yield 'dead end' => [ShipmentState::CANCELLED, ShipmentState::PREPARED, false];
        yield 'same state' => [ShipmentState::REQUESTED, ShipmentState::REQUESTED, false];
    }
}
