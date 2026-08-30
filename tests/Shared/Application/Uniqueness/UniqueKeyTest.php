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
        $key = UniqueKey::for(DummyUniqueKeyDiscriminator::EMAIL, ...$scope);

        // Then
        self::assertSame($expected, $key->toString());
    }

    /**
     * @return iterable<string, array{list<string>, string}>
     */
    public static function provideScopes(): iterable
    {
        yield 'no scope' => [[], 'dummy.email'];
        yield 'single scope segment' => [['a@b.example'], "dummy.email\x1Fa@b.example"];
        yield 'multiple scope segments' => [['a@b.example', 'tenant-1'], "dummy.email\x1Fa@b.example\x1Ftenant-1"];
    }

    #[Test]
    public function itComparesEquality(): void
    {
        // Given
        $a = UniqueKey::for(DummyUniqueKeyDiscriminator::EMAIL, 'a@b.example');
        $b = UniqueKey::for(DummyUniqueKeyDiscriminator::EMAIL, 'a@b.example');
        $differentScope = UniqueKey::for(DummyUniqueKeyDiscriminator::EMAIL, 'other@b.example');
        $differentDiscriminator = UniqueKey::for(DummyUniqueKeyDiscriminator::PHONE, 'a@b.example');

        // When
        $equalResult = $a->equals($b);
        $differentScopeResult = $a->equals($differentScope);
        $differentDiscriminatorResult = $a->equals($differentDiscriminator);

        // Then
        self::assertTrue($equalResult);
        self::assertFalse($differentScopeResult);
        self::assertFalse($differentDiscriminatorResult);
    }
}

enum DummyUniqueKeyDiscriminator: string
{
    case EMAIL = 'dummy.email';
    case PHONE = 'dummy.phone';
}
