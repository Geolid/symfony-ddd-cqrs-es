<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Uniqueness;

use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Exception\UniqueValueAlreadyTakenException;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Infrastructure\Uniqueness\DbalUniqueValueRegistry;
use Support\AbstractIntegrationTestCase;

final class DbalUniqueValueRegistryTest extends AbstractIntegrationTestCase
{
    private DbalUniqueValueRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = $this->service(DbalUniqueValueRegistry::class);
    }

    #[Test]
    public function itReserves(): void
    {
        // Given
        $key = UniqueKey::for(DummyUniqueKey::A);

        // When
        $this->registry->reserve($key, 'value', 'owner-1');

        // Then
        self::assertTrue($this->registry->exists($key, 'value'));
    }

    #[Test]
    public function itIgnoresWhenAlreadyReservedBySameOwner(): void
    {
        // Given
        $key = UniqueKey::for(DummyUniqueKey::A);
        $this->registry->reserve($key, 'value', 'owner-1');

        // When
        $this->registry->reserve($key, 'value', 'owner-1');

        // Then
        self::assertTrue($this->registry->exists($key, 'value'));
    }

    #[Test]
    public function itThrowsWhenAlreadyReservedByAnotherOwner(): void
    {
        // Given
        $key = UniqueKey::for(DummyUniqueKey::A);
        $this->registry->reserve($key, 'value', 'owner-1');

        // Then
        $this->expectException(UniqueValueAlreadyTakenException::class);

        // When
        $this->registry->reserve($key, 'value', 'owner-2');
    }

    #[Test]
    public function itReservesWithScope(): void
    {
        // Given
        $key = UniqueKey::for(DummyUniqueKey::A, 'scope-1');
        $otherScopeKey = UniqueKey::for(DummyUniqueKey::A, 'scope-2');

        // When
        $this->registry->reserve($key, 'value', 'owner-1');

        // Then
        self::assertTrue($this->registry->exists($key, 'value'));
        self::assertFalse($this->registry->exists($otherScopeKey, 'value'));
    }

    #[Test]
    public function itExcludesOwnReservation(): void
    {
        // Given
        $key = UniqueKey::for(DummyUniqueKey::A);
        $this->registry->reserve($key, 'value', 'owner-1');

        // When
        $exists = $this->registry->exists($key, 'value', excludeOwnerId: 'owner-1');

        // Then
        self::assertFalse($exists);
    }

    #[Test]
    public function itIncludesOthersReservation(): void
    {
        // Given
        $key = UniqueKey::for(DummyUniqueKey::A);
        $this->registry->reserve($key, 'value', 'owner-1');

        // When
        $exists = $this->registry->exists($key, 'value', excludeOwnerId: 'owner-2');

        // Then
        self::assertTrue($exists);
    }

    #[Test]
    public function itReleases(): void
    {
        // Given
        $key = UniqueKey::for(DummyUniqueKey::A);
        $otherKey = UniqueKey::for(DummyUniqueKey::B);
        $this->registry->reserve($key, 'value-1', 'owner-1');
        $this->registry->reserve($key, 'value-2', 'owner-1');
        $this->registry->reserve($otherKey, 'value-1', 'owner-1');

        // When
        $this->registry->release($key, 'value-1', 'owner-1');

        // Then
        self::assertFalse($this->registry->exists($key, 'value-1'));
        self::assertTrue($this->registry->exists($key, 'value-2'));
        self::assertTrue($this->registry->exists($otherKey, 'value-1'));
    }

    #[Test]
    public function itIgnoresWhenReleasedByAnotherOwner(): void
    {
        // Given
        $key = UniqueKey::for(DummyUniqueKey::A);
        $this->registry->reserve($key, 'value', 'owner-1');

        // When
        $this->registry->release($key, 'value', 'owner-2');

        // Then
        self::assertTrue($this->registry->exists($key, 'value'));
    }

    #[Test]
    public function itReleasesAll(): void
    {
        // Given
        $key = UniqueKey::for(DummyUniqueKey::A, 'scope-1');
        $otherScopeKey = UniqueKey::for(DummyUniqueKey::A, 'scope-2');
        $otherKey = UniqueKey::for(DummyUniqueKey::B, 'scope-1');
        $this->registry->reserve($key, 'value-1', 'owner-1');
        $this->registry->reserve($key, 'value-2', 'owner-1');
        $this->registry->reserve($otherScopeKey, 'value-1', 'owner-1');
        $this->registry->reserve($otherKey, 'value-1', 'owner-1');

        // When
        $this->registry->releaseAll($key);

        // Then
        self::assertFalse($this->registry->exists($key, 'value-1'));
        self::assertFalse($this->registry->exists($key, 'value-2'));
        self::assertTrue($this->registry->exists($otherScopeKey, 'value-1'));
        self::assertTrue($this->registry->exists($otherKey, 'value-1'));
    }

    #[Test]
    public function itReleasesAllForOwner(): void
    {
        // Given
        $key = UniqueKey::for(DummyUniqueKey::A);
        $this->registry->reserve($key, 'value-1', 'owner-1');
        $this->registry->reserve($key, 'value-2', 'owner-2');

        // When
        $this->registry->releaseAll($key, 'owner-1');

        // Then
        self::assertFalse($this->registry->exists($key, 'value-1'));
        self::assertTrue($this->registry->exists($key, 'value-2'));
    }
}

enum DummyUniqueKey: string
{
    case A = 'dummy.a';
    case B = 'dummy.b';
}
