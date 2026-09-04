<?php

declare(strict_types=1);

namespace Shared\Tests\Domain\Specification;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Domain\Specification\CanTransitionToSpecification;
use Shared\Tests\Support\Double\DummyState;

final class CanTransitionToSpecificationTest extends TestCase
{
    /** @var array<string, list<DummyState>> */
    private const array TRANSITIONS = [
        DummyState::INIT->value => [DummyState::PENDING, DummyState::FAILED],
        DummyState::PENDING->value => [DummyState::PROCESSING],
        DummyState::PROCESSING->value => [DummyState::SUCCESS, DummyState::FAILED],
        DummyState::SUCCESS->value => [],
        DummyState::FAILED->value => [],
    ];

    #[Test]
    #[DataProvider('provideTransitions')]
    public function itIsSatisfiedBy(DummyState $candidate, DummyState $target, bool $expected): void
    {
        // Given
        $specification = new CanTransitionToSpecification(self::TRANSITIONS, $target);

        // When
        $result = $specification->isSatisfiedBy($candidate);

        // Then
        self::assertSame($expected, $result);
    }

    /**
     * @return iterable<string, array{DummyState, DummyState, bool}>
     */
    public static function provideTransitions(): iterable
    {
        yield 'direct hop' => [DummyState::PENDING, DummyState::PROCESSING, true];
        yield 'first branch' => [DummyState::INIT, DummyState::PENDING, true];
        yield 'second branch' => [DummyState::INIT, DummyState::FAILED, true];
        yield 'two hops away' => [DummyState::INIT, DummyState::PROCESSING, false];
        yield 'backward' => [DummyState::PROCESSING, DummyState::PENDING, false];
        yield 'dead end' => [DummyState::FAILED, DummyState::PENDING, false];
        yield 'same state' => [DummyState::INIT, DummyState::INIT, false];
    }
}
