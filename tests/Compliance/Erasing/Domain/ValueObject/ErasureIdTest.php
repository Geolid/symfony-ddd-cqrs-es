<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasing\Domain\ValueObject;

use Compliance\Erasing\Domain\ValueObject\ErasureId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ErasureIdTest extends TestCase
{
    private const string KNOWN_ID = '0199a1b2-3c4d-7e5f-8061-72839405a6b7';

    #[Test]
    public function itCreatesFromString(): void
    {
        // When
        $id = ErasureId::fromString(self::KNOWN_ID);

        // Then
        self::assertSame(self::KNOWN_ID, $id->toString());
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function itProtectsInvariants(string $value): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        ErasureId::fromString($value);
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
        $a = ErasureId::fromString(self::KNOWN_ID);
        $b = ErasureId::fromString(self::KNOWN_ID);

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $a = ErasureId::fromString(self::KNOWN_ID);
        $b = ErasureId::fromString('0199a1b2-3c4d-7e5f-8061-72839405a6b8');

        // When
        $equals = $a->equals($b);

        // Then
        self::assertFalse($equals);
    }
}
