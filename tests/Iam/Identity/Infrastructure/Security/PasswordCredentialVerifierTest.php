<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Security;

use Iam\Identity\Application\Security\PasswordCredentialVerifierInterface;
use Iam\Identity\Domain\Service\SecretHasherInterface;
use Iam\Tests\Identity\Support\Factory\PasswordCredentialTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\AbstractIntegrationTestCase;

final class PasswordCredentialVerifierTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itVerifiesAMatchingSecret(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $credential = PasswordCredentialTestFactory::new()
            ->withIdentityId($identityId)
            ->withPassword('S3cr3t!')
            ->withHasher($this->service(SecretHasherInterface::class))
            ->create();
        $this->store($credential);

        // When
        $verified = $this->service(PasswordCredentialVerifierInterface::class)->verify($identityId, 'S3cr3t!');

        // Then
        self::assertTrue($verified);
    }

    #[Test]
    public function itRejectsAWrongSecret(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $credential = PasswordCredentialTestFactory::new()
            ->withIdentityId($identityId)
            ->withPassword('S3cr3t!')
            ->withHasher($this->service(SecretHasherInterface::class))
            ->create();
        $this->store($credential);

        // When
        $verified = $this->service(PasswordCredentialVerifierInterface::class)->verify($identityId, 'wrong');

        // Then
        self::assertFalse($verified);
    }
}
