<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Persistence\Projection\Finder;

use Iam\Identity\Application\Exception\PasswordCredentialResultNotFoundException;
use Iam\Identity\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Identity\Application\Status\IdentityStatus;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Tests\Identity\Support\Factory\PasswordCredentialTestFactory;
use Iam\Tests\Identity\Support\Stub\DummyPasswordPolicy;
use Iam\Tests\Identity\Support\Stub\DummySecretHasher;
use PHPUnit\Framework\Attributes\Test;
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
    public function itGetsAPasswordCredentialByLogin(): void
    {
        // Given
        $identityId = IdentityId::generate()->toString();
        $credential = PasswordCredentialTestFactory::new()
            ->withIdentityId($identityId)
            ->withLogin('operator')
            ->withPassword('S3cr3tPassw0rd!')
            ->withHasher(new DummySecretHasher())
            ->withPolicy(new DummyPasswordPolicy())
            ->store();

        // When
        $result = $this->finder->ofLogin('operator');

        // Then
        self::assertSame($credential->id()->toString(), $result->id);
        self::assertSame($identityId, $result->identityId);
        self::assertSame('operator', $result->login);
        self::assertSame('hashed:S3cr3tPassw0rd!', $result->hash);
        self::assertSame(IdentityStatus::ACTIVE, $result->identityStatus);
    }

    #[Test]
    public function itThrowsOnAnUnknownLogin(): void
    {
        // Then
        $this->expectException(PasswordCredentialResultNotFoundException::class);

        // When
        $this->finder->ofLogin('unknown@example.com');
    }

    #[Test]
    public function itGetsByIdentityId(): void
    {
        // Given
        $identityId = IdentityId::generate()->toString();
        $credential = PasswordCredentialTestFactory::new()
            ->withIdentityId($identityId)
            ->withLogin('operator')
            ->withPassword('S3cr3tPassw0rd!')
            ->withHasher(new DummySecretHasher())
            ->withPolicy(new DummyPasswordPolicy())
            ->store();

        // When
        $result = $this->finder->ofIdentityId($identityId);

        // Then
        self::assertSame($credential->id()->toString(), $result->id);
        self::assertSame($identityId, $result->identityId);
        self::assertSame('operator', $result->login);
        self::assertSame('hashed:S3cr3tPassw0rd!', $result->hash);
        self::assertSame(IdentityStatus::ACTIVE, $result->identityStatus);
    }

    #[Test]
    public function itThrowsOnAnUnknownIdentityId(): void
    {
        // Then
        $this->expectException(PasswordCredentialResultNotFoundException::class);

        // When
        $this->finder->ofIdentityId(IdentityId::generate()->toString());
    }
}
