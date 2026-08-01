<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Persistence\Lookup;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Domain\Exception\UniqueValueAlreadyTakenException;
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

        // When
        $this->registry->reserve(DummyUniqueValue::EMAIL, $value);

        // Then
        self::assertTrue($this->registry->exists(DummyUniqueValue::EMAIL, $value));
    }

    #[Test]
    public function itThrowsOnAValueAlreadyReserved(): void
    {
        // Given
        $value = Uuid::uuid7()->toString();
        $this->registry->reserve(DummyUniqueValue::EMAIL, $value);

        // Then
        $this->expectException(UniqueValueAlreadyTakenException::class);
        $this->expectExceptionMessage(\sprintf('Value "%s" is already in use for "dummy.email".', $value));

        // When
        $this->registry->reserve(DummyUniqueValue::EMAIL, $value);
    }

    #[Test]
    public function itReleasesOnlyTheReservationItIsGiven(): void
    {
        // Given
        $released = Uuid::uuid7()->toString();
        $kept = Uuid::uuid7()->toString();
        $this->registry->reserve(DummyUniqueValue::EMAIL, $released);
        $this->registry->reserve(DummyUniqueValue::EMAIL, $kept);
        $this->registry->reserve(DummyUniqueValue::PHONE, $released);

        // When
        $this->registry->release(DummyUniqueValue::EMAIL, $released);

        // Then
        self::assertFalse($this->registry->exists(DummyUniqueValue::EMAIL, $released));
        self::assertTrue($this->registry->exists(DummyUniqueValue::EMAIL, $kept));
        self::assertTrue($this->registry->exists(DummyUniqueValue::PHONE, $released));
    }
}

enum DummyUniqueValue: string
{
    case EMAIL = 'dummy.email';
    case PHONE = 'dummy.phone';
}
