<?php

declare(strict_types=1);

namespace Finance\Tests\Payer\Domain\ValueObject;

use Finance\Payer\Domain\ValueObject\PayerId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class PayerIdTest extends TestCase
{
    #[Test]
    public function itGenerates(): void
    {
        // When
        $id = PayerId::generate();

        // Then
        self::assertTrue(Uuid::isValid($id->toString()));
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function itProtectsInvariants(string $value): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        PayerId::fromString($value);
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
        $value = PayerId::generate()->toString();
        $a = PayerId::fromString($value);
        $b = PayerId::fromString($value);

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $a = PayerId::generate();
        $b = PayerId::generate();

        // When
        $equals = $a->equals($b);

        // Then
        self::assertFalse($equals);
    }
}
