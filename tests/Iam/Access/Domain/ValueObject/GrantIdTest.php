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
        $id = GrantId::forIdentityAndPermission('0199a1b2-3c4d-7e5f-8061-72839405a6b7', 'fixture.widget:read');

        // Then
        self::assertSame('8afaf9b6-dc05-5a0f-bab4-229aac3cd635', $id->toString());
    }

    #[Test]
    public function itDerivesTheSameIdForTheSameIdentityAndPermission(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();

        // When
        $a = GrantId::forIdentityAndPermission($identityId, 'fixture.widget:read');
        $b = GrantId::forIdentityAndPermission($identityId, 'fixture.widget:read');

        // Then
        self::assertTrue($a->equals($b));
    }

    #[Test]
    public function itDerivesADifferentIdForADifferentIdentity(): void
    {
        // When
        $a = GrantId::forIdentityAndPermission(Uuid::uuid7()->toString(), 'fixture.widget:read');
        $b = GrantId::forIdentityAndPermission(Uuid::uuid7()->toString(), 'fixture.widget:read');

        // Then
        self::assertFalse($a->equals($b));
    }

    #[Test]
    public function itDerivesADifferentIdForADifferentPermission(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();

        // When
        $a = GrantId::forIdentityAndPermission($identityId, 'fixture.widget:read');
        $b = GrantId::forIdentityAndPermission($identityId, 'fixture.widget:write');

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
