<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Security;

use Iam\Identity\Application\Security\ApiTokenCredentialVerifierInterface;
use Iam\Identity\Domain\Service\SecretHasherInterface;
use Iam\Tests\Identity\Support\Factory\ApiTokenCredentialTestFactory;
use Iam\Tests\Identity\Support\Helpers\ApiTokenTrait;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class ApiTokenCredentialVerifierTest extends AbstractIntegrationTestCase
{
    use ApiTokenTrait;

    private SecretHasherInterface $hasher;
    private ApiTokenCredentialVerifierInterface $verifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hasher = $this->service(SecretHasherInterface::class);
        $this->verifier = $this->service(ApiTokenCredentialVerifierInterface::class);
    }

    #[Test]
    public function itVerifiesAMatchingSecret(): void
    {
        // Given
        $identifier = $this->generateIdentifier();
        $credential = ApiTokenCredentialTestFactory::new()
            ->withIdentifier($identifier)
            ->withSecret('S3cr3t!')
            ->withHasher($this->hasher)
            ->create();
        $this->store($credential);

        // When
        $verified = $this->verifier->verify($identifier, 'S3cr3t!');

        // Then
        self::assertTrue($verified);
    }

    #[Test]
    public function itRejectsAWrongSecret(): void
    {
        // Given
        $identifier = $this->generateIdentifier();
        $credential = ApiTokenCredentialTestFactory::new()
            ->withIdentifier($identifier)
            ->withSecret('S3cr3t!')
            ->withHasher($this->hasher)
            ->create();
        $this->store($credential);

        // When
        $verified = $this->verifier->verify($identifier, 'wrong');

        // Then
        self::assertFalse($verified);
    }
}
