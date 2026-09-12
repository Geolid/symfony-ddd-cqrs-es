<?php

declare(strict_types=1);

namespace Crm\Tests\Customer\Domain\Customer\ValueObject;

use Crm\Customer\Domain\Customer\ValueObject\CustomerId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class CustomerIdTest extends TestCase
{
    private const string IDENTITY_ID = '0199a1b2-3c4d-7e5f-8061-72839405a6b7';

    #[Test]
    public function itGenerates(): void
    {
        // When
        $id = CustomerId::fromString(Uuid::uuid7()->toString());

        // Then
        self::assertTrue(Uuid::isValid($id->toString()));
    }

    #[Test]
    public function itDerivesKnownId(): void
    {
        // When
        $id = CustomerId::forIdentity(self::IDENTITY_ID);

        // Then
        self::assertSame('fee8fa79-34b5-5bb0-b5cf-676c35c7fdbf', $id->toString());
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function itProtectsInvariants(string $value): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        CustomerId::fromString($value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'empty string' => [''];
        yield 'invalid uuid' => ['not-a-uuid'];
    }

    #[Test]
    public function itEquals(): void
    {
        // Given
        $value = Uuid::uuid7()->toString();
        $a = CustomerId::fromString($value);
        $b = CustomerId::fromString($value);

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $a = CustomerId::fromString(Uuid::uuid7()->toString());
        $b = CustomerId::fromString(Uuid::uuid7()->toString());

        // When
        $equals = $a->equals($b);

        // Then
        self::assertFalse($equals);
    }
}
