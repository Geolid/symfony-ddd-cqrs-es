<?php

declare(strict_types=1);

namespace Iam\Tests\Access\Domain\ValueObject;

use Iam\Access\Domain\ValueObject\Permission;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PermissionTest extends TestCase
{
    #[Test]
    public function itCreates(): void
    {
        $permission = Permission::fromString('fixture:write');

        self::assertSame('fixture:write', $permission->toString());
    }

    #[Test]
    public function itComparesEquality(): void
    {
        $a = Permission::fromString('fixture:write');
        $b = Permission::fromString('fixture:write');
        $other = Permission::fromString('fixture:read');

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($other));
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function itProtectsInvariants(string $value): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Permission::fromString($value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'empty string' => [''];
        yield 'missing the action segment' => ['fixture'];
        yield 'missing the subdomain segment' => [':write'];
        yield 'uppercase' => ['Fixture:Write'];
        yield 'contains whitespace' => ['fixture:has space'];
    }
}
