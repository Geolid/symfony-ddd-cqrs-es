<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Domain\ValueObject;

use Iam\Identity\Domain\ValueObject\Reason;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ReasonTest extends TestCase
{
    #[Test]
    public function itCreates(): void
    {
        // When
        $reason = Reason::fromString('Suspected fraudulent activity');

        // Then
        self::assertSame('Suspected fraudulent activity', $reason->value);
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function itProtectsInvariants(string $value): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        Reason::fromString($value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'empty string' => [''];
        yield 'whitespace only' => ['   '];
        yield 'too long' => [str_repeat('a', 256)];
    }

    #[Test]
    public function itNormalizes(): void
    {
        // When
        $reason = Reason::fromString('  Suspected fraudulent activity  ');

        // Then
        self::assertSame('Suspected fraudulent activity', $reason->value);
    }

    #[Test]
    public function itComparesEquality(): void
    {
        // When
        $a = Reason::fromString('Suspected fraudulent activity');
        $b = Reason::fromString('Suspected fraudulent activity');

        // Then
        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals(Reason::fromString('Appeal upheld')));
    }
}
