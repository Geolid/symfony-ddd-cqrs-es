<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sales\Customer\Domain\ValueObject\CustomerId;

final class CustomerIdTest extends TestCase
{
    #[Test]
    public function itGenerates(): void
    {
        // When
        $id = CustomerId::generate();

        // Then
        self::assertNotEmpty($id->toString());
    }

    #[Test]
    public function itComparesEquality(): void
    {
        // Given
        $value = CustomerId::generate()->toString();

        // When
        $a = CustomerId::fromString($value);
        $b = CustomerId::fromString($value);

        // Then
        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals(CustomerId::generate()));
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
}
