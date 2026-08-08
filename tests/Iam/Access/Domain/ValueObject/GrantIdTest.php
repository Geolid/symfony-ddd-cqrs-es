<?php

declare(strict_types=1);

namespace Iam\Tests\Access\Domain\ValueObject;

use Iam\Access\Domain\ValueObject\GrantId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class GrantIdTest extends TestCase
{
    #[Test]
    public function itDerivesAKnownIdForAKnownIdentityAndPermission(): void
    {
        // When
        $id = GrantId::forIdentityAndPermission('an-identity-id', 'fixture:read');

        // Then
        self::assertSame('594e4acb-4d4c-504e-bb2f-9ab047e9e4be', $id->toString());
    }

    #[Test]
    public function itDerivesTheSameIdForTheSameIdentityAndPermission(): void
    {
        // When
        $a = GrantId::forIdentityAndPermission('an-identity-id', 'fixture:read');
        $b = GrantId::forIdentityAndPermission('an-identity-id', 'fixture:read');

        // Then
        self::assertTrue($a->equals($b));
    }

    #[Test]
    public function itDerivesADifferentIdForADifferentIdentity(): void
    {
        // When
        $a = GrantId::forIdentityAndPermission('an-identity-id', 'fixture:read');
        $b = GrantId::forIdentityAndPermission('another-identity-id', 'fixture:read');

        // Then
        self::assertFalse($a->equals($b));
    }

    #[Test]
    public function itDerivesADifferentIdForADifferentPermission(): void
    {
        // When
        $a = GrantId::forIdentityAndPermission('an-identity-id', 'fixture:read');
        $b = GrantId::forIdentityAndPermission('an-identity-id', 'fixture:write');

        // Then
        self::assertFalse($a->equals($b));
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function itProtectsInvariants(string $value): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        GrantId::fromString($value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'empty string' => [''];
        yield 'invalid uuid' => ['not-a-uuid'];
    }
}
