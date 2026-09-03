<?php

declare(strict_types=1);

namespace Shared\Tests\Domain\ValueObject;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Domain\ValueObject\CountryCode;

final class CountryCodeTest extends TestCase
{
    #[Test]
    public function itListsValues(): void
    {
        // When
        $values = CountryCode::values();
        $cases = CountryCode::cases();

        // Then
        self::assertSame(
            array_map(static fn (CountryCode $case): string => $case->name, $cases),
            array_map(static fn (CountryCode $case): string => $case->value, $cases),
        );
        self::assertSame(array_map(static fn (CountryCode $case): string => $case->value, $cases), $values);
        self::assertSame(\count($cases), \count(array_unique($values)));
        self::assertContains('ZZ', $values);
    }
}
