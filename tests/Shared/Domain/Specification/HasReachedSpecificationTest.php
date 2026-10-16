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
    #[DataProvider('provideReachedTargets')]
    public function itAcceptsWhenTargetReached(DummyState $candidate, DummyState $target): void
    {
        // Given
        $specification = new HasReachedSpecification(self::TRANSITIONS, $target);

        // When
        $result = $specification->isSatisfiedBy($candidate);

        // Then
        self::assertTrue($result);
    }

    /**
     * @return iterable<string, array{DummyState, DummyState}>
     */
    public static function provideReachedTargets(): iterable
    {
        yield 'same state' => [DummyState::PENDING, DummyState::PENDING];
        yield 'one hop away' => [DummyState::PROCESSING, DummyState::PENDING];
        yield 'several hops away' => [DummyState::SUCCESS, DummyState::PENDING];
        yield 'dead end itself' => [DummyState::FAILED, DummyState::FAILED];
    }

    #[Test]
    #[DataProvider('provideUnreachedTargets')]
    public function itRefusesWhenTargetNotReached(DummyState $candidate, DummyState $target): void
    {
        // Given
        $specification = new HasReachedSpecification(self::TRANSITIONS, $target);

        // When
        $result = $specification->isSatisfiedBy($candidate);

        // Then
        self::assertFalse($result);
    }

    /**
     * @return iterable<string, array{DummyState, DummyState}>
     */
    public static function provideUnreachedTargets(): iterable
    {
        yield 'backward' => [DummyState::PENDING, DummyState::PROCESSING];
        yield 'other branch' => [DummyState::PENDING, DummyState::FAILED];
        yield 'dead end' => [DummyState::FAILED, DummyState::SUCCESS];
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
    public function itAcceptsWhenIntBackedTargetReached(): void
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
