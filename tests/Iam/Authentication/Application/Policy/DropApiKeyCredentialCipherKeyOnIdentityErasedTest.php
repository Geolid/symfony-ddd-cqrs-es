<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Policy;

use Iam\Authentication\Application\Policy\DropApiKeyCredentialCipherKeyOnIdentityErased;
use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use Iam\Tests\Authentication\Support\Builder\ApiKeyCredentialBuilder;
use Patchlevel\Hydrator\Extension\Cryptography\Cipher\CipherKey;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyNotExists;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;
use Webmozart\Assert\Assert;

final class DropApiKeyCredentialCipherKeyOnIdentityErasedTest extends AbstractIntegrationTestCase
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
        $identityId = ApiKeyCredentialBuilder::sample('identityId');
        Assert::stringNotEmpty($identityId);
        $now = Clock::get()->now();
        $this->cipherKeyStore->store(new CipherKey(
            id: Uuid::uuid7()->toString(),
            subjectId: $identityId,
            key: 'fake-key',
            method: 'aes256',
            createdAt: $now,
        ));

        // When
        $this->trigger(DropApiKeyCredentialCipherKeyOnIdentityErased::class, new IdentityErasedIntegrationEvent($identityId, $now));

        // Then
        $this->expectException(CipherKeyNotExists::class);
        $this->cipherKeyStore->currentKeyFor($identityId);
    }
}
