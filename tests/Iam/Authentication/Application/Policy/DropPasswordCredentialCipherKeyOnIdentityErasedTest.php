<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Policy;

use Iam\Authentication\Application\Policy\DropPasswordCredentialCipherKeyOnIdentityErased;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;
use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Patchlevel\Hydrator\Extension\Cryptography\Cipher\CipherKey;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyNotExists;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class DropPasswordCredentialCipherKeyOnIdentityErasedTest extends AbstractIntegrationTestCase
{
    private CipherKeyStore $cipherKeyStore;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cipherKeyStore = $this->service(CipherKeyStore::class);
    }

    #[Test]
    public function itDrops(): void
    {
        // Given
        $identityId = PasswordCredentialBuilder::sample('identityId');
        $passwordCredentialId = PasswordCredentialId::forIdentity($identityId)->toString();
        $now = Clock::get()->now();
        $this->cipherKeyStore->store(new CipherKey(
            id: Uuid::uuid7()->toString(),
            subjectId: $passwordCredentialId,
            key: 'fake-key',
            method: 'aes256',
            createdAt: $now,
        ));

        // When
        $this->trigger(DropPasswordCredentialCipherKeyOnIdentityErased::class, new IdentityErasedIntegrationEvent($identityId, $now));

        // Then
        $this->expectException(CipherKeyNotExists::class);
        $this->cipherKeyStore->currentKeyFor($passwordCredentialId);
    }
}
