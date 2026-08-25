<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Persistence\Lookup;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Domain\Exception\UniqueValueAlreadyTakenException;
use Shared\Domain\ValueObject\UniqueKey;
use Shared\Infrastructure\Persistence\Lookup\DbalUniqueValueRegistry;
use Support\AbstractIntegrationTestCase;

final class DbalUniqueValueRegistryTest extends AbstractIntegrationTestCase
{
    private DbalUniqueValueRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = $this->service(DbalUniqueValueRegistry::class);
    }

    #[Test]
    public function itReservesAValue(): void
    {
        // Given
        $value = Uuid::uuid7()->toString();
        $ownerId = Uuid::uuid7()->toString();

        // When
        $this->registry->reserve(UniqueKey::for(DummyUniqueKey::EMAIL), $value, $ownerId);

        // Then
        self::assertTrue($this->registry->exists(UniqueKey::for(DummyUniqueKey::EMAIL), $value));
    }

    #[Test]
    public function itIgnoresARetryFromTheSameOwner(): void
    {
        // Given
        $value = Uuid::uuid7()->toString();
        $ownerId = Uuid::uuid7()->toString();
        $this->registry->reserve(UniqueKey::for(DummyUniqueKey::EMAIL), $value, $ownerId);

        // When
        $this->registry->reserve(UniqueKey::for(DummyUniqueKey::EMAIL), $value, $ownerId);

        // Then
        self::assertTrue($this->registry->exists(UniqueKey::for(DummyUniqueKey::EMAIL), $value));
    }

    #[Test]
    public function itReservesACompositeKey(): void
    {
        // Given
        $value = Uuid::uuid7()->toString();
        $scope = Uuid::uuid7()->toString();
        $ownerId = Uuid::uuid7()->toString();

        // When
        $this->registry->reserve(UniqueKey::for(DummyUniqueKey::EMAIL, $scope), $value, $ownerId);

        // Then
        self::assertTrue($this->registry->exists(UniqueKey::for(DummyUniqueKey::EMAIL, $scope), $value));
        self::assertFalse($this->registry->exists(UniqueKey::for(DummyUniqueKey::EMAIL, Uuid::uuid7()->toString()), $value));
    }

    #[Test]
    public function itIgnoresItsOwnReservationWhenExcludingItsOwner(): void
    {
        // Given
        $value = Uuid::uuid7()->toString();
        $ownerId = Uuid::uuid7()->toString();
        $this->registry->reserve(UniqueKey::for(DummyUniqueKey::EMAIL), $value, $ownerId);

        // When
        $exists = $this->registry->exists(UniqueKey::for(DummyUniqueKey::EMAIL), $value, $ownerId);

        // Then
        self::assertFalse($exists);
    }

    #[Test]
    public function itFindsAValueReservedBySomeoneElseWhileExcludingAnotherOwner(): void
    {
        // Given
        $value = Uuid::uuid7()->toString();
        $this->registry->reserve(UniqueKey::for(DummyUniqueKey::EMAIL), $value, Uuid::uuid7()->toString());

        // When
        $exists = $this->registry->exists(UniqueKey::for(DummyUniqueKey::EMAIL), $value, Uuid::uuid7()->toString());

        // Then
        self::assertTrue($exists);
    }

    #[Test]
    public function itThrowsOnAValueAlreadyReservedByAnotherOwner(): void
    {
        // Given
        $value = Uuid::uuid7()->toString();
        $this->registry->reserve(UniqueKey::for(DummyUniqueKey::EMAIL), $value, Uuid::uuid7()->toString());

        // Then
        $this->expectException(UniqueValueAlreadyTakenException::class);

        // When
        $this->registry->reserve(UniqueKey::for(DummyUniqueKey::EMAIL), $value, Uuid::uuid7()->toString());
    }

    #[Test]
    public function itReleasesOnlyTheReservationItIsGiven(): void
    {
        // Given
        $released = Uuid::uuid7()->toString();
        $kept = Uuid::uuid7()->toString();
        $ownerId = Uuid::uuid7()->toString();
        $this->registry->reserve(UniqueKey::for(DummyUniqueKey::EMAIL), $released, $ownerId);
        $this->registry->reserve(UniqueKey::for(DummyUniqueKey::EMAIL), $kept, $ownerId);
        $this->registry->reserve(UniqueKey::for(DummyUniqueKey::PHONE), $released, $ownerId);

        // When
        $this->registry->release(UniqueKey::for(DummyUniqueKey::EMAIL), $released, $ownerId);

        // Then
        self::assertFalse($this->registry->exists(UniqueKey::for(DummyUniqueKey::EMAIL), $released));
        self::assertTrue($this->registry->exists(UniqueKey::for(DummyUniqueKey::EMAIL), $kept));
        self::assertTrue($this->registry->exists(UniqueKey::for(DummyUniqueKey::PHONE), $released));
    }

    #[Test]
    public function itKeepsTheReservationWhenReleasedByAnotherOwner(): void
    {
        // Given
        $value = Uuid::uuid7()->toString();
        $this->registry->reserve(UniqueKey::for(DummyUniqueKey::EMAIL), $value, Uuid::uuid7()->toString());

        // When
        $this->registry->release(UniqueKey::for(DummyUniqueKey::EMAIL), $value, Uuid::uuid7()->toString());

        // Then
        self::assertTrue($this->registry->exists(UniqueKey::for(DummyUniqueKey::EMAIL), $value));
    }

    #[Test]
    public function itReleasesEveryValueUnderTheGivenKey(): void
    {
        // Given
        $scope = Uuid::uuid7()->toString();
        $otherScope = Uuid::uuid7()->toString();
        $first = Uuid::uuid7()->toString();
        $second = Uuid::uuid7()->toString();
        $keptOtherScope = Uuid::uuid7()->toString();
        $keptOtherDiscriminator = Uuid::uuid7()->toString();
        $this->registry->reserve(UniqueKey::for(DummyUniqueKey::EMAIL, $scope), $first, Uuid::uuid7()->toString());
        $this->registry->reserve(UniqueKey::for(DummyUniqueKey::EMAIL, $scope), $second, Uuid::uuid7()->toString());
        $this->registry->reserve(UniqueKey::for(DummyUniqueKey::EMAIL, $otherScope), $keptOtherScope, Uuid::uuid7()->toString());
        $this->registry->reserve(UniqueKey::for(DummyUniqueKey::PHONE, $scope), $keptOtherDiscriminator, Uuid::uuid7()->toString());

        // When
        $this->registry->releaseAll(UniqueKey::for(DummyUniqueKey::EMAIL, $scope));

        // Then
        self::assertFalse($this->registry->exists(UniqueKey::for(DummyUniqueKey::EMAIL, $scope), $first));
        self::assertFalse($this->registry->exists(UniqueKey::for(DummyUniqueKey::EMAIL, $scope), $second));
        self::assertTrue($this->registry->exists(UniqueKey::for(DummyUniqueKey::EMAIL, $otherScope), $keptOtherScope));
        self::assertTrue($this->registry->exists(UniqueKey::for(DummyUniqueKey::PHONE, $scope), $keptOtherDiscriminator));
    }

    #[Test]
    public function itReleasesOnlyTheGivenOwnerUnderTheKey(): void
    {
        // Given
        $released = Uuid::uuid7()->toString();
        $kept = Uuid::uuid7()->toString();
        $ownerId = Uuid::uuid7()->toString();
        $otherOwnerId = Uuid::uuid7()->toString();
        $this->registry->reserve(UniqueKey::for(DummyUniqueKey::EMAIL), $released, $ownerId);
        $this->registry->reserve(UniqueKey::for(DummyUniqueKey::EMAIL), $kept, $otherOwnerId);

        // When
        $this->registry->releaseAll(UniqueKey::for(DummyUniqueKey::EMAIL), $ownerId);

        // Then
        self::assertFalse($this->registry->exists(UniqueKey::for(DummyUniqueKey::EMAIL), $released));
        self::assertTrue($this->registry->exists(UniqueKey::for(DummyUniqueKey::EMAIL), $kept));
    }
}

enum DummyUniqueKey: string
{
    case EMAIL = 'dummy.email';
    case PHONE = 'dummy.phone';
}
