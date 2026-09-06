<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Projection;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Infrastructure\Projection\SnakeCaseKeys;

final class SnakeCaseKeysTest extends TestCase
{
    /**
     * @param array<array-key, mixed> $data
     * @param array<array-key, mixed> $expected
     */
    #[Test]
    #[DataProvider('provideCamelCaseArrays')]
    public function itConverts(array $data, array $expected): void
    {
        // When
        $result = SnakeCaseKeys::from($data);

        // Then
        self::assertSame($expected, $result);
    }

    /**
     * @return iterable<string, array{array<array-key, mixed>, array<array-key, mixed>}>
     */
    public static function provideCamelCaseArrays(): iterable
    {
        yield 'flat key' => [
            ['camelCaseKey' => 'value'],
            ['camel_case_key' => 'value'],
        ];

        yield 'nested array value' => [
            ['outerKey' => ['innerKey' => 'value']],
            ['outer_key' => ['inner_key' => 'value']],
        ];

        yield 'non-array values of any type stay untouched' => [
            ['flagKey' => true, 'countKey' => 3],
            ['flag_key' => true, 'count_key' => 3],
        ];

        yield 'already snake_case key stays unchanged' => [
            ['already_snake' => 'value'],
            ['already_snake' => 'value'],
        ];
    }
}
