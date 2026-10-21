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
    public function itCanTransitionWhenEdgeDirect(DummyState $current, DummyState $target): void
    {
        // Given
        $transitions = StateTransitions::of(self::TRANSITIONS);

        // When
        $result = $transitions->can($current, $target);

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
    public function itCannotTransitionWhenEdgeMissing(DummyState $current, DummyState $target): void
    {
        // Given
        $transitions = StateTransitions::of(self::TRANSITIONS);

        // When
        $result = $transitions->can($current, $target);

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
    public function itCannotTransitionWhenStateUndeclared(): void
    {
        // Given
        $transitions = StateTransitions::of([
            DummyState::INIT->value => [DummyState::PENDING],
            DummyState::PENDING->value => [],
        ]);

        // When
        $result = $transitions->can(DummyState::SUCCESS, DummyState::PENDING);

        // Then
        self::assertFalse($result);
    }

    /**
     * @param array<array-key, mixed> $transitions
     */
    #[Test]
    #[DataProvider('provideInvalidTransitions')]
    public function itProtectsInvariants(array $transitions): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        StateTransitions::of($transitions);
    }

    /**
     * @return iterable<string, array{array<array-key, mixed>}>
     */
    public static function provideInvalidTransitions(): iterable
    {
        yield 'no state' => [[]];
        yield 'targets not an array' => [[DummyState::INIT->value => 'pending']];
        yield 'targets not a list' => [[DummyState::INIT->value => ['first' => DummyState::INIT]]];
        yield 'target not an enum' => [[DummyState::INIT->value => ['pending'], 'pending' => []]];
        yield 'target undeclared' => [[DummyState::INIT->value => [DummyState::PENDING]]];
    }
}
