<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Security;

use Iam\Identity\Application\Security\ApiTokenCredentialVerifierInterface;
use Iam\Identity\Domain\Service\SecretHasherInterface;
use Iam\Tests\Identity\Support\Factory\ApiTokenCredentialTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class ApiTokenCredentialVerifierTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itVerifiesAMatchingSecret(): void
    {
        // Given
        $identifier = 'key_'.bin2hex(random_bytes(4));
        $credential = ApiTokenCredentialTestFactory::new()
            ->withIdentifier($identifier)
            ->withSecret('S3cr3t!')
            ->withHasher($this->service(SecretHasherInterface::class))
            ->create();
        $this->store($credential);

        // When
        $verified = $this->service(ApiTokenCredentialVerifierInterface::class)->verify($identifier, 'S3cr3t!');

        // Then
        self::assertTrue($verified);
    }

    #[Test]
    public function itRejectsAWrongSecret(): void
    {
        // Given
        $identifier = 'key_'.bin2hex(random_bytes(4));
        $credential = ApiTokenCredentialTestFactory::new()
            ->withIdentifier($identifier)
            ->withSecret('S3cr3t!')
            ->withHasher($this->service(SecretHasherInterface::class))
            ->create();
        $this->store($credential);

        // When
        $verified = $this->service(ApiTokenCredentialVerifierInterface::class)->verify($identifier, 'wrong');

        // Then
        self::assertFalse($verified);
    }
}
