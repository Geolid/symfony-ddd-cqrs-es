<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Persistence\Projection\Finder;

use Iam\Authentication\Application\Exception\PasswordCredentialResultNotFoundException;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Tests\Authentication\Support\Doubles\StubPasswordHasher;
use Iam\Tests\Authentication\Support\Doubles\StubPasswordStrength;
use Iam\Tests\Authentication\Support\Factory\PasswordCredentialTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\AbstractIntegrationTestCase;

final class DbalPasswordCredentialFinderTest extends AbstractIntegrationTestCase
{
    private PasswordCredentialFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(PasswordCredentialFinderInterface::class);
    }

    #[Test]
    public function itGetsByLogin(): void
    {
        // Given
        $hasher = new StubPasswordHasher();
        $credential = PasswordCredentialTestFactory::new()
            ->withLogin('ada.lovelace')
            ->withPassword('original-password')
            ->withDefinedAt(new \DateTimeImmutable('2026-01-01T00:00:00+00:00'))
            ->withPasswordStrength(new StubPasswordStrength())
            ->withHasher($hasher)
            ->create();
        $this->store($credential);

        // When
        $result = $this->finder->ofLogin('ada.lovelace');

        // Then
        self::assertSame($credential->id->toString(), $result->id);
        self::assertSame('ada.lovelace', $result->login);
        self::assertSame($hasher->hash('original-password'), $result->passwordHash);
        self::assertSame('2026-01-01T00:00:00+00:00', $result->definedAt->format('c'));
        self::assertSame('2026-01-01T00:00:00+00:00', $result->passwordChangedAt->format('c'));
        self::assertTrue($result->identityAuthenticatable);
    }

    #[Test]
    public function itThrowsWhenLoginNotFound(): void
    {
        // Then
        $this->expectException(PasswordCredentialResultNotFoundException::class);

        // When
        $this->finder->ofLogin('unknown.login');
    }

    #[Test]
    public function itGetsByIdentityId(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $credential = PasswordCredentialTestFactory::new()
            ->withIdentityId($identityId)
            ->withPasswordStrength(new StubPasswordStrength())
            ->withHasher(new StubPasswordHasher())
            ->create();
        $this->store($credential);

        // When
        $result = $this->finder->ofIdentityId($identityId);

        // Then
        self::assertSame($credential->id->toString(), $result->id);
        self::assertSame($identityId, $result->identityId);
    }

    #[Test]
    public function itThrowsWhenIdentityNotFound(): void
    {
        // Then
        $this->expectException(PasswordCredentialResultNotFoundException::class);

        // When
        $this->finder->ofIdentityId(Uuid::uuid7()->toString());
    }
}
