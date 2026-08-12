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
    #[DataProvider('provideAcceptedValues')]
    public function itCreates(string $value): void
    {
        $permission = Permission::fromString($value);

        self::assertSame($value, $permission->toString());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideAcceptedValues(): iterable
    {
        yield 'permission' => ['fixture.widget:write'];
        yield 'maximum length' => [str_pad('fixture.widget:write', 255, 'write')];
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
        yield 'missing the action segment' => ['fixture.widget'];
        yield 'missing the bc segment' => ['fixture:write'];
        yield 'missing the subdomain segment' => ['.widget:write'];
        yield 'uppercase' => ['Fixture.Widget:Write'];
        yield 'contains whitespace' => ['fixture.widget:has space'];
        yield 'too long' => [str_pad('fixture.widget:write', 256, 'write')];
    }

    #[Test]
    public function itComparesEquality(): void
    {
        $a = Permission::fromString('fixture.widget:write');
        $b = Permission::fromString('fixture.widget:write');
        $other = Permission::fromString('fixture.widget:read');

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($other));
    }
}
