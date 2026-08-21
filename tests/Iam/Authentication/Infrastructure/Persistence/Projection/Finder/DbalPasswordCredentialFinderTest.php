<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Persistence\Projection\Finder;

use Iam\Authentication\Application\Exception\PasswordCredentialResultNotFoundException;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordPolicyInterface;
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
        $hasher = $this->service(PasswordHasherInterface::class);
        $credential = PasswordCredentialTestFactory::new()
            ->withLogin('ada.lovelace')
            ->withPassword('Xk9$mQ2vLp7&zR4w')
            ->withPolicy($this->service(PasswordPolicyInterface::class))
            ->withHasher($hasher)
            ->store();

        // When
        $result = $this->finder->ofLogin('ada.lovelace');

        // Then
        self::assertSame($credential->id->toString(), $result->id);
        self::assertSame('ada.lovelace', $result->login);
        self::assertTrue($hasher->verify($result->passwordHash, 'Xk9$mQ2vLp7&zR4w'));
        self::assertTrue($result->identityAuthenticatable);
    }

    #[Test]
    public function itGetsByIdentityId(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $credential = PasswordCredentialTestFactory::new()
            ->withIdentityId($identityId)
            ->withPolicy($this->service(PasswordPolicyInterface::class))
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->store();

        // When
        $result = $this->finder->ofIdentityId($identityId);

        // Then
        self::assertSame($credential->id->toString(), $result->id);
        self::assertSame($identityId, $result->identityId);
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
    public function itThrowsWhenIdentityNotFound(): void
    {
        // Then
        $this->expectException(PasswordCredentialResultNotFoundException::class);

        // When
        $this->finder->ofIdentityId(Uuid::uuid7()->toString());
    }
}
