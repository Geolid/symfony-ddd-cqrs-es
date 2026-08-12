<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\RehashPasswordCredential;

use Iam\Identity\Application\Command\RehashPasswordCredential\RehashPasswordCredential;
use Iam\Identity\Application\Exception\PasswordCredentialResultNotFoundException;
use Iam\Identity\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Identity\Domain\Service\SecretHasherInterface;
use Iam\Tests\Identity\Support\Factory\PasswordCredentialTestFactory;
use Iam\Tests\Identity\Support\Stub\DummySecretHasher;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\AbstractIntegrationTestCase;

final class RehashPasswordCredentialHandlerTest extends AbstractIntegrationTestCase
{
    private SecretHasherInterface $hasher;
    private PasswordCredentialFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hasher = $this->service(SecretHasherInterface::class);
        $this->finder = $this->service(PasswordCredentialFinderInterface::class);
    }

    #[Test]
    public function itRehashesAnOutdatedPasswordCredential(): void
    {
        // Given — DummySecretHasher's format never matches the real hasher, so it always looks outdated.
        $identityId = Uuid::uuid7()->toString();
        $credential = PasswordCredentialTestFactory::new()
            ->withIdentityId($identityId)
            ->withPassword('S3cr3t!')
            ->withHasher(new DummySecretHasher())
            ->store();
        $staleHash = $this->finder->ofIdentityId($identityId)->hash;

        // When
        $this->dispatch(new RehashPasswordCredential($identityId, 'S3cr3t!'));

        // Then
        $result = $this->finder->ofIdentityId($identityId);
        self::assertNotSame($staleHash, $result->hash);
        self::assertTrue($this->hasher->verify($result->hash, 'S3cr3t!'));
    }

    #[Test]
    public function itIgnoresAPasswordCredentialThatIsAlreadyUpToDate(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $credential = PasswordCredentialTestFactory::new()
            ->withIdentityId($identityId)
            ->withPassword('S3cr3t!')
            ->withHasher($this->hasher)
            ->store();
        $currentHash = $this->finder->ofIdentityId($identityId)->hash;

        // When
        $this->dispatch(new RehashPasswordCredential($identityId, 'S3cr3t!'));

        // Then
        $result = $this->finder->ofIdentityId($identityId);
        self::assertSame($currentHash, $result->hash);
    }

    #[Test]
    public function itFailsWhenTheCredentialDoesNotExist(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();

        // Then
        $this->expectException(PasswordCredentialResultNotFoundException::class);

        // When
        $this->dispatch(new RehashPasswordCredential($identityId, 'S3cr3t!'));
    }
}
