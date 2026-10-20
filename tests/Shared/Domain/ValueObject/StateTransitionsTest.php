<?php

declare(strict_types=1);

namespace Shared\Tests\Domain\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Domain\ValueObject\StateTransitions;
use Shared\Tests\Support\Double\DummyState;

final class StateTransitionsTest extends TestCase
{
    /** @var array<string, list<DummyState>> */
    private const array TRANSITIONS = [
        DummyState::INIT->value => [DummyState::PENDING, DummyState::FAILED],
        DummyState::PENDING->value => [DummyState::PROCESSING],
        DummyState::PROCESSING->value => [DummyState::SUCCESS, DummyState::FAILED, DummyState::PENDING],
        DummyState::SUCCESS->value => [],
        DummyState::FAILED->value => [],
    ];

    #[Test]
    #[DataProvider('provideAllowedTransitions')]
    public function itAllowsWhenEdgeDirect(DummyState $from, DummyState $to): void
    {
        // Given
        $transitions = new StateTransitions(self::TRANSITIONS);

        // When
        $result = $transitions->allows($from, $to);

        // Then
        self::assertTrue($result);
    }

    /**
     * @return iterable<string, array{DummyState, DummyState}>
     */
    public static function provideAllowedTransitions(): iterable
    {
        yield 'direct hop' => [DummyState::PENDING, DummyState::PROCESSING];
        yield 'first branch' => [DummyState::INIT, DummyState::PENDING];
        yield 'second branch' => [DummyState::INIT, DummyState::FAILED];
        yield 'retry' => [DummyState::PROCESSING, DummyState::PENDING];
    }

    #[Test]
    #[DataProvider('provideRefusedTransitions')]
    public function itDoesNotAllowWhenEdgeIndirect(DummyState $from, DummyState $to): void
    {
        // Given
        $transitions = new StateTransitions(self::TRANSITIONS);

        // When
        $result = $transitions->allows($from, $to);

        // Then
        self::assertFalse($result);
    }

    /**
     * @return iterable<string, array{DummyState, DummyState}>
     */
    public static function provideRefusedTransitions(): iterable
    {
        yield 'two hops away' => [DummyState::INIT, DummyState::PROCESSING];
        yield 'backward' => [DummyState::PENDING, DummyState::INIT];
        yield 'dead end' => [DummyState::FAILED, DummyState::PENDING];
        yield 'same state' => [DummyState::INIT, DummyState::INIT];
    }

    #[Test]
    #[DataProvider('provideReachedTargets')]
    public function itReachesWhenPathExists(DummyState $from, DummyState $to): void
    {
        // Given
        $transitions = new StateTransitions(self::TRANSITIONS);

        // When
        $result = $transitions->reaches($from, $to);

        // Then
        self::assertTrue($result);
    }

    /**
     * @return iterable<string, array{DummyState, DummyState}>
     */
    public static function provideReachedTargets(): iterable
    {
        yield 'same state' => [DummyState::PENDING, DummyState::PENDING];
        yield 'several hops' => [DummyState::INIT, DummyState::SUCCESS];
        yield 'through retry loop' => [DummyState::PENDING, DummyState::FAILED];
    }

    #[Test]
    #[DataProvider('provideUnreachedTargets')]
    public function itDoesNotReachWhenNoPath(DummyState $from, DummyState $to): void
    {
        // Given
        $transitions = new StateTransitions(self::TRANSITIONS);

        // When
        $result = $transitions->reaches($from, $to);

        // Then
        self::assertFalse($result);
    }

    /**
     * @return iterable<string, array{DummyState, DummyState}>
     */
    public static function provideUnreachedTargets(): iterable
    {
        yield 'backward' => [DummyState::PENDING, DummyState::INIT];
        yield 'dead end' => [DummyState::FAILED, DummyState::SUCCESS];
        yield 'around retry loop' => [DummyState::PROCESSING, DummyState::INIT];
    }
}
