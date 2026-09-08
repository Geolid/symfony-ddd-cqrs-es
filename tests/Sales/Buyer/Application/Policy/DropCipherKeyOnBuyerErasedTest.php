<?php

declare(strict_types=1);

namespace Sales\Tests\Buyer\Application\Policy;

use Patchlevel\Hydrator\Extension\Cryptography\Cipher\CipherKey;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyNotExists;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Buyer\Application\Policy\DropCipherKeyOnBuyerErased;
use Sales\Buyer\Domain\Event\BuyerErased;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class DropCipherKeyOnBuyerErasedTest extends AbstractIntegrationTestCase
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
        $buyer = BuyerBuilder::new()->create();
        $this->store($buyer);
        $buyerId = $buyer->id->toString();
        $now = Clock::get()->now();
        $this->cipherKeyStore->store(new CipherKey(
            id: Uuid::uuid7()->toString(),
            subjectId: $buyerId,
            key: 'fake-key',
            method: 'aes256',
            createdAt: $now,
        ));

        // When
        $this->trigger(DropCipherKeyOnBuyerErased::class, new BuyerErased($buyerId, $now));

        // Then
        $this->expectException(CipherKeyNotExists::class);
        $this->cipherKeyStore->currentKeyFor($buyerId);
    }
}
