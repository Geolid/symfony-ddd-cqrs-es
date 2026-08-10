<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\RehashApiTokenCredential;

use Iam\Identity\Application\Command\RehashApiTokenCredential\RehashApiTokenCredential;
use Iam\Identity\Application\Exception\ApiTokenCredentialResultNotFoundException;
use Iam\Identity\Application\Finder\ApiTokenCredential\ApiTokenCredentialFinderInterface;
use Iam\Identity\Domain\Service\SecretHasherInterface;
use Iam\Tests\Identity\Support\Factory\ApiTokenCredentialTestFactory;
use Iam\Tests\Identity\Support\Helpers\ApiTokenTrait;
use Iam\Tests\Identity\Support\Stub\DummySecretHasher;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class RehashApiTokenCredentialHandlerTest extends AbstractIntegrationTestCase
{
    use ApiTokenTrait;

    private SecretHasherInterface $hasher;
    private ApiTokenCredentialFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hasher = $this->service(SecretHasherInterface::class);
        $this->finder = $this->service(ApiTokenCredentialFinderInterface::class);
    }

    #[Test]
    public function itRehashesAnOutdatedApiTokenCredential(): void
    {
        // Given — DummySecretHasher's format never matches the real hasher, so it always looks outdated.
        $identifier = $this->generateIdentifier();
        $credential = ApiTokenCredentialTestFactory::new()
            ->withIdentifier($identifier)
            ->withSecret('S3cr3t!')
            ->withHasher(new DummySecretHasher())
            ->create();
        $this->store($credential);
        $staleHash = $this->finder->ofIdentifier($identifier)->hash;

        // When
        $this->dispatch(new RehashApiTokenCredential($identifier, 'S3cr3t!'));

        // Then
        $result = $this->finder->ofIdentifier($identifier);
        self::assertNotSame($staleHash, $result->hash);
        self::assertTrue($this->hasher->verify($result->hash, 'S3cr3t!'));
    }

    #[Test]
    public function itIgnoresAnApiTokenCredentialThatIsAlreadyUpToDate(): void
    {
        // Given
        $identifier = $this->generateIdentifier();
        $credential = ApiTokenCredentialTestFactory::new()
            ->withIdentifier($identifier)
            ->withSecret('S3cr3t!')
            ->withHasher($this->hasher)
            ->create();
        $this->store($credential);
        $currentHash = $this->finder->ofIdentifier($identifier)->hash;

        // When
        $this->dispatch(new RehashApiTokenCredential($identifier, 'S3cr3t!'));

        // Then
        $result = $this->finder->ofIdentifier($identifier);
        self::assertSame($currentHash, $result->hash);
    }

    #[Test]
    public function itFailsWhenTheCredentialDoesNotExist(): void
    {
        // Given
        $identifier = 'key_unknown';

        // Then
        $this->expectException(ApiTokenCredentialResultNotFoundException::class);

        // When
        $this->dispatch(new RehashApiTokenCredential($identifier, 'S3cr3t!'));
    }
}
