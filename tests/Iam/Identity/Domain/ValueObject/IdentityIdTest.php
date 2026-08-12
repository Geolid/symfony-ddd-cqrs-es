<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Domain\ValueObject;

use Iam\Identity\Domain\ValueObject\IdentityId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class IdentityIdTest extends TestCase
{
    #[Test]
    public function itGenerates(): void
    {
        // When
        $id = IdentityId::generate();

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
        IdentityId::fromString($value);
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
    public function itComparesEquality(): void
    {
        // Given
        $value = IdentityId::generate()->toString();

        // When
        $a = IdentityId::fromString($value);
        $b = IdentityId::fromString($value);

        // Then
        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals(IdentityId::generate()));
    }
}
