<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\RehashApiTokenCredential;

use Iam\Identity\Application\Command\RehashApiTokenCredential\RehashApiTokenCredential;
use Iam\Identity\Application\Exception\ApiTokenCredentialResultNotFoundException;
use Iam\Identity\Application\Finder\ApiTokenCredential\ApiTokenCredentialFinderInterface;
use Iam\Identity\Domain\Service\SecretHasherInterface;
use Iam\Tests\Identity\Support\Factory\ApiTokenCredentialTestFactory;
use Iam\Tests\Identity\Support\Stub\DummySecretHasher;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class RehashApiTokenCredentialHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRehashesAnOutdatedApiTokenCredential(): void
    {
        // Given — DummySecretHasher's format never matches the real hasher, so it always looks outdated.
        $identifier = 'key_'.bin2hex(random_bytes(4));
        $credential = ApiTokenCredentialTestFactory::new()
            ->withIdentifier($identifier)
            ->withSecret('S3cr3t!')
            ->withHasher(new DummySecretHasher())
            ->create();
        $this->store($credential);
        $staleHash = $this->service(ApiTokenCredentialFinderInterface::class)->ofIdentifier($identifier)->hash;

        // When
        $this->dispatch(new RehashApiTokenCredential($identifier, 'S3cr3t!'));

        // Then
        $result = $this->service(ApiTokenCredentialFinderInterface::class)->ofIdentifier($identifier);
        self::assertNotSame($staleHash, $result->hash);
        self::assertTrue($this->service(SecretHasherInterface::class)->verify($result->hash, 'S3cr3t!'));
    }

    #[Test]
    public function itIgnoresAnApiTokenCredentialThatIsAlreadyUpToDate(): void
    {
        // Given
        $identifier = 'key_'.bin2hex(random_bytes(4));
        $credential = ApiTokenCredentialTestFactory::new()
            ->withIdentifier($identifier)
            ->withSecret('S3cr3t!')
            ->withHasher($this->service(SecretHasherInterface::class))
            ->create();
        $this->store($credential);
        $currentHash = $this->service(ApiTokenCredentialFinderInterface::class)->ofIdentifier($identifier)->hash;

        // When
        $this->dispatch(new RehashApiTokenCredential($identifier, 'S3cr3t!'));

        // Then
        $result = $this->service(ApiTokenCredentialFinderInterface::class)->ofIdentifier($identifier);
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
