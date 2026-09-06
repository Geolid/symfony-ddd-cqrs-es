<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Projection;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Infrastructure\Projection\SnakeCaseKeys;

final class SnakeCaseKeysTest extends TestCase
{
    #[Test]
    public function itConvertsCamelCaseKeys(): void
    {
        // When
        $result = SnakeCaseKeys::from(['recipientName' => 'Ada Lovelace', 'postalCode' => '75001']);

        // Then
        self::assertSame(['recipient_name' => 'Ada Lovelace', 'postal_code' => '75001'], $result);
    }

    #[Test]
    public function itConvertsNestedArrayKeysRecursively(): void
    {
        // When
        $result = SnakeCaseKeys::from(['recipientName' => 'Ada Lovelace', 'address' => ['postalCode' => '75001', 'countryCode' => 'FR']]);

        // Then
        self::assertSame(['recipient_name' => 'Ada Lovelace', 'address' => ['postal_code' => '75001', 'country_code' => 'FR']], $result);
    }

    #[Test]
    public function itLeavesNonArrayValuesUntouched(): void
    {
        // When
        $result = SnakeCaseKeys::from(['isActive' => true, 'itemCount' => 3]);

        // Then
        self::assertSame(['is_active' => true, 'item_count' => 3], $result);
    }
}
