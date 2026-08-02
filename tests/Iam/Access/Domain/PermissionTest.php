<?php

declare(strict_types=1);

namespace Iam\Tests\Access\Domain;

use Iam\Access\Domain\Permission;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PermissionTest extends TestCase
{
    #[Test]
    public function itCreates(): void
    {
        $permission = Permission::fromString('sales:order_write');

        self::assertSame('sales:order_write', $permission->toString());
    }

    #[Test]
    public function itComparesEquality(): void
    {
        $a = Permission::fromString('sales:order_write');
        $b = Permission::fromString('sales:order_write');
        $other = Permission::fromString('sales:order_read');

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

    #[Test]
    public function itValidatesAWellFormattedValue(): void
    {
        self::assertTrue(Permission::isValid('sales:order_write'));
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function itInvalidatesAMalformedValue(string $value): void
    {
        self::assertFalse(Permission::isValid($value));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'empty string' => [''];
        yield 'missing the action segment' => ['sales'];
        yield 'missing the subdomain segment' => [':order_write'];
        yield 'uppercase' => ['Sales:Order_Write'];
        yield 'contains whitespace' => ['sales:order write'];
    }
}
