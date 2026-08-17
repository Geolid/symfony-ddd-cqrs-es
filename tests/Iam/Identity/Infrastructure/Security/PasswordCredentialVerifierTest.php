<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Security;

use Iam\Identity\Application\Credential\PasswordCredentialVerifierInterface;
use Iam\Identity\Domain\Service\PasswordPolicyInterface;
use Iam\Identity\Domain\Service\SecretHasherInterface;
use Iam\Tests\Identity\Support\Factory\PasswordCredentialTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\AbstractIntegrationTestCase;

final class PasswordCredentialVerifierTest extends AbstractIntegrationTestCase
{
    private SecretHasherInterface $hasher;
    private PasswordPolicyInterface $policy;
    private PasswordCredentialVerifierInterface $verifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hasher = $this->service(SecretHasherInterface::class);
        $this->policy = $this->service(PasswordPolicyInterface::class);
        $this->verifier = $this->service(PasswordCredentialVerifierInterface::class);
    }

    #[Test]
    public function itVerifiesAMatchingSecret(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $credential = PasswordCredentialTestFactory::new()
            ->withIdentityId($identityId)
            ->withPassword('MyStr0ngP@ssw0rd123!')
            ->withHasher($this->hasher)
            ->withPolicy($this->policy)
            ->store();

        // When
        $verified = $this->verifier->verify($identityId, 'MyStr0ngP@ssw0rd123!');

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
            ->withPassword('MyStr0ngP@ssw0rd123!')
            ->withHasher($this->hasher)
            ->withPolicy($this->policy)
            ->store();

        // When
        $verified = $this->verifier->verify($identityId, 'wrong');

        // Then
        self::assertFalse($verified);
    }
}
