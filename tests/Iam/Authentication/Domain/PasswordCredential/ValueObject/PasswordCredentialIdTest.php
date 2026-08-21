<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Domain\PasswordCredential\ValueObject;

use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class PasswordCredentialIdTest extends TestCase
{
    #[Test]
    public function itDerivesKnownId(): void
    {
        // When
        $id = PasswordCredentialId::forIdentity('0199a1b2-3c4d-7e5f-8061-72839405a6b7');

        // Then
        self::assertSame('5269238e-008a-5f7e-8865-0cb12051d9b6', $id->toString());
    }

    #[Test]
    public function itComparesEquality(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();

        // When
        $a = PasswordCredentialId::forIdentity($identityId);
        $b = PasswordCredentialId::forIdentity($identityId);

        // Then
        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals(PasswordCredentialId::forIdentity(Uuid::uuid7()->toString())));
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function itProtectsInvariants(string $value): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        PasswordCredentialId::fromString($value);
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
