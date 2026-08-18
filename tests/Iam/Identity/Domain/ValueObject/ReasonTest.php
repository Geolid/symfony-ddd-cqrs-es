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
    #[DataProvider('provideAcceptedValues')]
    public function itCreates(string $value, string $expected): void
    {
        // When
        $reason = Reason::fromString($value);

        // Then
        self::assertSame($expected, $reason->value);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideAcceptedValues(): iterable
    {
        yield 'reason' => ['Suspected fraudulent activity', 'Suspected fraudulent activity'];
        yield 'maximum length' => [str_pad('Suspected fraudulent activity', 255, 'x'), str_pad('Suspected fraudulent activity', 255, 'x')];
        yield 'surrounding whitespace' => ['  Suspected fraudulent activity  ', 'Suspected fraudulent activity'];
    }

    #[Test]
    public function itComparesEquality(): void
    {
        // Given
        $a = Reason::fromString('Suspected fraudulent activity');
        $b = Reason::fromString('  Suspected fraudulent activity  ');
        $other = Reason::fromString('Requested by the account holder');

        // When
        $equalResult = $a->equals($b);
        $differentResult = $a->equals($other);

        // Then
        self::assertTrue($equalResult);
        self::assertFalse($differentResult);
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
        yield 'too long' => [str_pad('Suspected fraudulent activity', 256, 'x')];
    }
}
