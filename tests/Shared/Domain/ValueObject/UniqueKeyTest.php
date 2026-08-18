<?php

declare(strict_types=1);

namespace Shared\Tests\Domain\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Domain\ValueObject\UniqueKey;

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
        yield 'single scope segment' => [['a@b.com'], "dummy.email\x1Fa@b.com"];
        yield 'multiple scope segments' => [['a@b.com', 'tenant-1'], "dummy.email\x1Fa@b.com\x1Ftenant-1"];
    }

    #[Test]
    public function itComparesEquality(): void
    {
        // Given
        $a = UniqueKey::for(DummyUniqueKeyDiscriminator::EMAIL, 'a@b.com');
        $b = UniqueKey::for(DummyUniqueKeyDiscriminator::EMAIL, 'a@b.com');
        $differentScope = UniqueKey::for(DummyUniqueKeyDiscriminator::EMAIL, 'other@b.com');
        $differentDiscriminator = UniqueKey::for(DummyUniqueKeyDiscriminator::PHONE, 'a@b.com');

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
