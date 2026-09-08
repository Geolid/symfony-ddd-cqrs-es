<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\DropApiKeyCredentialCipherKeysOfIdentity;

use Iam\Authentication\Application\Command\DropApiKeyCredentialCipherKeysOfIdentity\DropApiKeyCredentialCipherKeysOfIdentity;
use Iam\Tests\Authentication\Support\Builder\ApiKeyCredentialBuilder;
use Iam\Tests\Authentication\Support\Double\FakeApiKeyHasher;
use Patchlevel\Hydrator\Extension\Cryptography\Cipher\CipherKey;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyNotExists;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class DropApiKeyCredentialCipherKeysOfIdentityHandlerTest extends AbstractIntegrationTestCase
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
        $this->dispatch(new DropApiKeyCredentialCipherKeysOfIdentity($identityId));

        // Then
        $otherKey = $this->cipherKeyStore->currentKeyFor($other->id->toString());
        self::assertSame($other->id->toString(), $otherKey->subjectId);

        $this->expectException(CipherKeyNotExists::class);
        $this->cipherKeyStore->currentKeyFor($credential->id->toString());
    }

    #[Test]
    public function itIgnoresWhenNoneExist(): void
    {
        // Given
        $identityId = ApiKeyCredentialBuilder::sample('identityId');

        // When
        $this->dispatch(new DropApiKeyCredentialCipherKeysOfIdentity($identityId));

        // Then
        self::expectNotToPerformAssertions();
    }
}
