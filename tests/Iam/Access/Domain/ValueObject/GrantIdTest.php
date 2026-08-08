<?php

declare(strict_types=1);

namespace Iam\Tests\Access\Domain\ValueObject;

use Iam\Access\Domain\ValueObject\GrantId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class GrantIdTest extends TestCase
{
    #[Test]
    public function itDerivesAKnownIdForAKnownIdentityAndPermission(): void
    {
        // When
        $id = GrantId::forIdentityAndPermission('0199a1b2-3c4d-7e5f-8061-72839405a6b7', 'fixture:read');

        // Then
        self::assertSame('d7ade99a-1608-589a-94e9-7eba625bd6f1', $id->toString());
    }

    #[Test]
    public function itDerivesTheSameIdForTheSameIdentityAndPermission(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();

        // When
        $a = GrantId::forIdentityAndPermission($identityId, 'fixture:read');
        $b = GrantId::forIdentityAndPermission($identityId, 'fixture:read');

        // Then
        self::assertTrue($a->equals($b));
    }

    #[Test]
    public function itDerivesADifferentIdForADifferentIdentity(): void
    {
        // When
        $a = GrantId::forIdentityAndPermission(Uuid::uuid7()->toString(), 'fixture:read');
        $b = GrantId::forIdentityAndPermission(Uuid::uuid7()->toString(), 'fixture:read');

        // Then
        self::assertFalse($a->equals($b));
    }

    #[Test]
    public function itDerivesADifferentIdForADifferentPermission(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();

        // When
        $a = GrantId::forIdentityAndPermission($identityId, 'fixture:read');
        $b = GrantId::forIdentityAndPermission($identityId, 'fixture:write');

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
