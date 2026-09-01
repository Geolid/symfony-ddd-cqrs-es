<?php

declare(strict_types=1);

namespace Shared\Tests\Application\Uniqueness;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Application\Uniqueness\UniqueKey;

final class UniqueKeyTest extends TestCase
{
    /**
     * @param list<string> $scope
     */
    #[Test]
    #[DataProvider('provideScopes')]
    public function itCreates(array $scope, string $expected): void
    {
        // When
        $key = UniqueKey::for(DummyUniqueKey::A, ...$scope);

        // Then
        self::assertSame($expected, $key->toString());
    }

    /**
     * @return iterable<string, array{list<string>, string}>
     */
    public static function provideScopes(): iterable
    {
        yield 'no scope' => [[], 'dummy.a'];
        yield 'single scope segment' => [['scope-a'], "dummy.a\x1Fscope-a"];
        yield 'multiple scope segments' => [['scope-a', 'scope-b'], "dummy.a\x1Fscope-a\x1Fscope-b"];
    }

    #[Test]
    public function itEquals(): void
    {
        // Given
        $a = UniqueKey::for(DummyUniqueKey::A, 'scope-a');
        $b = UniqueKey::for(DummyUniqueKey::A, 'scope-a');

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $a = UniqueKey::for(DummyUniqueKey::A, 'scope-a');
        $differentScope = UniqueKey::for(DummyUniqueKey::A, 'scope-b');
        $differentDiscriminator = UniqueKey::for(DummyUniqueKey::B, 'scope-a');

        // When
        $differsOnScope = $a->equals($differentScope);
        $differsOnDiscriminator = $a->equals($differentDiscriminator);

        // Then
        self::assertFalse($differsOnScope);
        self::assertFalse($differsOnDiscriminator);
    }
}

enum DummyUniqueKey: string
{
    case A = 'dummy.a';
    case B = 'dummy.b';
}
