<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\DropApiKeyCredentialCipherKey;

use Iam\Authentication\Application\Command\DropApiKeyCredentialCipherKey\DropApiKeyCredentialCipherKey;
use Patchlevel\Hydrator\Extension\Cryptography\Cipher\CipherKey;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyNotExists;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class DropApiKeyCredentialCipherKeyHandlerTest extends AbstractIntegrationTestCase
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
        $id = Uuid::uuid7()->toString();
        $this->cipherKeyStore->store(new CipherKey(
            id: Uuid::uuid7()->toString(),
            subjectId: $id,
            key: 'fake-key',
            method: 'aes256',
            createdAt: Clock::get()->now(),
        ));

        // When
        $this->dispatch(new DropApiKeyCredentialCipherKey($id));

        // Then
        $this->expectException(CipherKeyNotExists::class);
        $this->cipherKeyStore->currentKeyFor($id);
    }
}
