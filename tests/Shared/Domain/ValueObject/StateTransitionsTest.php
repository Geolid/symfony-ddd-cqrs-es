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
        DummyState::PROCESSING->value => [DummyState::SUCCESS, DummyState::FAILED],
        DummyState::SUCCESS->value => [],
        DummyState::FAILED->value => [],
    ];

    /** @var array<string, list<DummyState>> */
    private const array CYCLIC_TRANSITIONS = [
        DummyState::INIT->value => [DummyState::PENDING],
        DummyState::PENDING->value => [DummyState::INIT, DummyState::SUCCESS],
        DummyState::SUCCESS->value => [],
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
    }

    #[Test]
    #[DataProvider('provideForbiddenTransitions')]
    public function itRefusesWhenEdgeNotDirect(DummyState $from, DummyState $to): void
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
    public static function provideForbiddenTransitions(): iterable
    {
        yield 'two hops away' => [DummyState::INIT, DummyState::PROCESSING];
        yield 'backward' => [DummyState::PROCESSING, DummyState::PENDING];
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
        yield 'one hop' => [DummyState::PENDING, DummyState::PROCESSING];
        yield 'several hops' => [DummyState::INIT, DummyState::SUCCESS];
        yield 'dead end itself' => [DummyState::FAILED, DummyState::FAILED];
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
        yield 'backward' => [DummyState::PROCESSING, DummyState::PENDING];
        yield 'other branch' => [DummyState::PENDING, DummyState::FAILED];
        yield 'dead end' => [DummyState::FAILED, DummyState::SUCCESS];
    }

    #[Test]
    public function itReachesAcrossCycle(): void
    {
        // Given
        $transitions = new StateTransitions(self::CYCLIC_TRANSITIONS);

        // When
        $result = $transitions->reaches(DummyState::INIT, DummyState::SUCCESS);

        // Then
        self::assertTrue($result);
    }

    #[Test]
    public function itDoesNotReachAcrossCycle(): void
    {
        // Given
        $transitions = new StateTransitions(self::CYCLIC_TRANSITIONS);

        // When
        $result = $transitions->reaches(DummyState::INIT, DummyState::FAILED);

        // Then
        self::assertFalse($result);
    }

    #[Test]
    public function itReachesWhenIntBacked(): void
    {
        // Given
        /** @var array<string, list<DummyIntState>> $table */
        $table = [
            DummyIntState::FIRST->value => [DummyIntState::SECOND],
            DummyIntState::SECOND->value => [],
        ];
        $transitions = new StateTransitions($table);

        // When
        $result = $transitions->reaches(DummyIntState::FIRST, DummyIntState::SECOND);

        // Then
        self::assertTrue($result);
    }
}

enum DummyIntState: int
{
    case FIRST = 1;
    case SECOND = 2;
}
