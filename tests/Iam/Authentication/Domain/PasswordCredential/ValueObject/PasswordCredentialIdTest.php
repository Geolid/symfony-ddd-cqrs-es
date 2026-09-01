<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Domain\PasswordCredential\ValueObject;

use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PasswordCredentialIdTest extends TestCase
{
    private const string IDENTITY_ID = '0199a1b2-3c4d-7e5f-8061-72839405a6b7';

    #[Test]
    public function itDerivesKnownId(): void
    {
        // When
        $id = PasswordCredentialId::forIdentity(self::IDENTITY_ID);

        // Then
        self::assertSame('5269238e-008a-5f7e-8865-0cb12051d9b6', $id->toString());
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

    #[Test]
    public function itEquals(): void
    {
        // Given
        $a = PasswordCredentialId::forIdentity(self::IDENTITY_ID);
        $b = PasswordCredentialId::forIdentity(self::IDENTITY_ID);

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $a = PasswordCredentialId::forIdentity(self::IDENTITY_ID);
        $b = PasswordCredentialId::forIdentity('0199a1b2-3c4d-7e5f-8061-72839405a6b8');

        // When
        $equals = $a->equals($b);

        // Then
        self::assertFalse($equals);
    }
}
