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
    #[DataProvider('provideAllowedTransitions')]
    public function itAcceptsWhenTransitionAllowed(DummyState $candidate, DummyState $target): void
    {
        // Given
        $specification = new CanTransitionToSpecification(self::TRANSITIONS, $target);

        // When
        $result = $specification->isSatisfiedBy($candidate);

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
    public function itRefusesWhenTransitionForbidden(DummyState $candidate, DummyState $target): void
    {
        // Given
        $specification = new CanTransitionToSpecification(self::TRANSITIONS, $target);

        // When
        $result = $specification->isSatisfiedBy($candidate);

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
}
