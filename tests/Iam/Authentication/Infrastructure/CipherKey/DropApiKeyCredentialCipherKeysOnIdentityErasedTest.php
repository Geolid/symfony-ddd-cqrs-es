<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\CipherKey;

use Iam\Authentication\Infrastructure\CipherKey\DropApiKeyCredentialCipherKeysOnIdentityErased;
use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use Iam\Tests\Authentication\Support\Builder\ApiKeyCredentialBuilder;
use Iam\Tests\Authentication\Support\Double\FakeApiKeyHasher;
use Patchlevel\Hydrator\Extension\Cryptography\Cipher\CipherKey;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyNotExists;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class DropApiKeyCredentialCipherKeysOnIdentityErasedTest extends AbstractIntegrationTestCase
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
        $hasher = new FakeApiKeyHasher();
        $other = ApiKeyCredentialBuilder::new()->withHasher($hasher)->create();

        $identityId = ApiKeyCredentialBuilder::sample('identityId');
        $credential = ApiKeyCredentialBuilder::new()->withIdentityId($identityId)->withHasher($hasher)->create();
        $this->store($other, $credential);

        $now = Clock::get()->now();
        $this->cipherKeyStore->store(new CipherKey(id: Uuid::uuid7()->toString(), subjectId: $other->id->toString(), key: 'fake-key', method: 'aes256', createdAt: $now));
        $this->cipherKeyStore->store(new CipherKey(id: Uuid::uuid7()->toString(), subjectId: $credential->id->toString(), key: 'fake-key', method: 'aes256', createdAt: $now));

        // When
        $this->trigger(DropApiKeyCredentialCipherKeysOnIdentityErased::class, new IdentityErasedIntegrationEvent($identityId, $now));

        // Then
        $otherKey = $this->cipherKeyStore->currentKeyFor($other->id->toString());
        self::assertSame($other->id->toString(), $otherKey->subjectId);

        $this->expectException(CipherKeyNotExists::class);
        $this->cipherKeyStore->currentKeyFor($credential->id->toString());
    }
}
