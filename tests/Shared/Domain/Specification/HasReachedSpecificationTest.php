<?php

declare(strict_types=1);

namespace Shared\Tests\Domain\Specification;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Domain\Specification\HasReachedSpecification;
use Shared\Tests\Support\Double\DummyState;

final class HasReachedSpecificationTest extends TestCase
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
        $specification = new HasReachedSpecification(self::TRANSITIONS, $target);

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
        yield 'same state' => [DummyState::PENDING, DummyState::PENDING, true];
        yield 'one hop away' => [DummyState::PROCESSING, DummyState::PENDING, true];
        yield 'several hops away' => [DummyState::SUCCESS, DummyState::PENDING, true];
        yield 'backward' => [DummyState::PENDING, DummyState::PROCESSING, false];
        yield 'other branch' => [DummyState::PENDING, DummyState::FAILED, false];
        yield 'dead end itself' => [DummyState::FAILED, DummyState::FAILED, true];
        yield 'dead end' => [DummyState::FAILED, DummyState::SUCCESS, false];
    }

    #[Test]
    public function itProtectsInvariantsWhenMutuallyReachable(): void
    {
        // Given
        $transitions = [
            DummyState::PENDING->value => [DummyState::PROCESSING],
            DummyState::PROCESSING->value => [DummyState::PENDING],
        ];

        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        new HasReachedSpecification($transitions, DummyState::PENDING);
    }

    #[Test]
    public function itProtectsInvariantsWhenSelfReferencing(): void
    {
        // Given
        $transitions = [
            DummyState::INIT->value => [DummyState::FAILED],
            DummyState::FAILED->value => [],
            DummyState::PENDING->value => [DummyState::FAILED, DummyState::PENDING],
        ];

        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        new HasReachedSpecification($transitions, DummyState::PENDING);
    }

    #[Test]
    public function itIsSatisfiedByWithIntBackedStates(): void
    {
        // Given
        /** @var array<string, list<DummyIntState>> $transitions */
        $transitions = [
            DummyIntState::FIRST->value => [DummyIntState::SECOND],
            DummyIntState::SECOND->value => [],
        ];
        $specification = new HasReachedSpecification($transitions, DummyIntState::FIRST);

        // When
        $result = $specification->isSatisfiedBy(DummyIntState::SECOND);

        // Then
        self::assertTrue($result);
    }
}

enum DummyIntState: int
{
    case FIRST = 1;
    case SECOND = 2;
}
