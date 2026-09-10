<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Uniqueness;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Uniqueness\Exception\UniquenessViolatedException;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Infrastructure\Uniqueness\DbalUniquenessRegistry;
use Shared\Tests\Support\Double\DummyUniqueKey;
use Support\TestCase\AbstractIntegrationTestCase;

final class DbalUniquenessRegistryTest extends AbstractIntegrationTestCase
{
    private DbalUniquenessRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = $this->service(DbalUniquenessRegistry::class);
    }

    #[Test]
    public function itClaims(): void
    {
        // Given
        $key = UniqueKey::for(DummyUniqueKey::NAME);

        // When
        $this->registry->claim($key, 'value', 'owner-1');

        // Then
        self::assertTrue($this->registry->isClaimed($key, 'value'));
    }

    #[Test]
    public function itIgnoresWhenAlreadyClaimedBySameOwner(): void
    {
        // Given
        $key = UniqueKey::for(DummyUniqueKey::NAME);
        $this->registry->claim($key, 'value', 'owner-1');

        // When
        $this->registry->claim($key, 'value', 'owner-1');

        // Then
        self::assertTrue($this->registry->isClaimed($key, 'value'));
    }

    #[Test]
    public function itThrowsWhenAlreadyClaimedByAnotherOwner(): void
    {
        // Given
        $key = UniqueKey::for(DummyUniqueKey::NAME);
        $this->registry->claim($key, 'value', 'owner-1');

        // Then
        $this->expectException(UniquenessViolatedException::class);

        // When
        $this->registry->claim($key, 'value', 'owner-2');
    }

    #[Test]
    public function itClaimsWithScope(): void
    {
        // Given
        $key = UniqueKey::for(DummyUniqueKey::NAME, 'scope-1');
        $otherScopeKey = UniqueKey::for(DummyUniqueKey::NAME, 'scope-2');

        // When
        $this->registry->claim($key, 'value', 'owner-1');

        // Then
        self::assertTrue($this->registry->isClaimed($key, 'value'));
        self::assertFalse($this->registry->isClaimed($otherScopeKey, 'value'));
    }

    #[Test]
    #[DataProvider('provideExclusionOutcomes')]
    public function itExcludesOnlyOwnClaim(string $excludeOwnerId, bool $expected): void
    {
        // Given
        $key = UniqueKey::for(DummyUniqueKey::NAME);
        $this->registry->claim($key, 'value', 'owner-1');

        // When
        $isClaimed = $this->registry->isClaimed($key, 'value', excludeOwnerId: $excludeOwnerId);

        // Then
        self::assertSame($expected, $isClaimed);
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function provideExclusionOutcomes(): iterable
    {
        yield 'own claim' => ['owner-1', false];
        yield 'another owner' => ['owner-2', true];
    }

    #[Test]
    public function itReleases(): void
    {
        // Given
        $key = UniqueKey::for(DummyUniqueKey::NAME);
        $otherKey = UniqueKey::for(DummyUniqueKey::CODE);
        $this->registry->claim($key, 'value-1', 'owner-1');
        $this->registry->claim($key, 'value-2', 'owner-2');
        $this->registry->claim($otherKey, 'value-3', 'owner-1');

        // When
        $this->registry->release($key, 'owner-1');

        // Then
        self::assertFalse($this->registry->isClaimed($key, 'value-1'));
        self::assertTrue($this->registry->isClaimed($key, 'value-2'));
        self::assertTrue($this->registry->isClaimed($otherKey, 'value-3'));
    }

    #[Test]
    public function itReleasesAll(): void
    {
        // Given
        $key = UniqueKey::for(DummyUniqueKey::NAME, 'scope-1');
        $otherScopeKey = UniqueKey::for(DummyUniqueKey::NAME, 'scope-2');
        $otherKey = UniqueKey::for(DummyUniqueKey::CODE, 'scope-1');
        $this->registry->claim($key, 'value-1', 'owner-1');
        $this->registry->claim($key, 'value-2', 'owner-2');
        $this->registry->claim($otherScopeKey, 'value-1', 'owner-1');
        $this->registry->claim($otherKey, 'value-1', 'owner-1');

        // When
        $this->registry->releaseAll($key);

        // Then
        self::assertFalse($this->registry->isClaimed($key, 'value-1'));
        self::assertFalse($this->registry->isClaimed($key, 'value-2'));
        self::assertTrue($this->registry->isClaimed($otherScopeKey, 'value-1'));
        self::assertTrue($this->registry->isClaimed($otherKey, 'value-1'));
    }
}
