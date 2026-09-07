<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasure\Application\Policy;

use Compliance\Erasure\Application\Policy\DropCipherKeyOnSubjectErased;
use Compliance\Erasure\Domain\Event\SubjectErased;
use Patchlevel\Hydrator\Extension\Cryptography\Cipher\CipherKey;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyNotExists;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class DropCipherKeyOnSubjectErasedTest extends AbstractIntegrationTestCase
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
        $now = Clock::get()->now();
        $subjectId = Uuid::uuid7()->toString();
        $this->cipherKeyStore->store(new CipherKey(
            id: Uuid::uuid7()->toString(),
            subjectId: $subjectId,
            key: 'fake-key',
            method: 'aes256',
            createdAt: $now,
        ));

        // When
        $this->trigger(DropCipherKeyOnSubjectErased::class, new SubjectErased($subjectId, $now->modify('+1 day')));

        // Then
        $this->expectException(CipherKeyNotExists::class);
        $this->cipherKeyStore->currentKeyFor($subjectId);
    }
}
