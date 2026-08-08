<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Domain\ValueObject;

use Iam\Identity\Domain\ValueObject\PasswordCredentialId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class PasswordCredentialIdTest extends TestCase
{
    #[Test]
    public function itDerivesTheSameIdForTheSameIdentity(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();

        // When
        $a = PasswordCredentialId::forIdentity($identityId);
        $b = PasswordCredentialId::forIdentity($identityId);

        // Then
        self::assertTrue($a->equals($b));
    }

    #[Test]
    public function itDerivesADifferentIdForADifferentIdentity(): void
    {
        // When
        $a = PasswordCredentialId::forIdentity(Uuid::uuid7()->toString());
        $b = PasswordCredentialId::forIdentity(Uuid::uuid7()->toString());

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
