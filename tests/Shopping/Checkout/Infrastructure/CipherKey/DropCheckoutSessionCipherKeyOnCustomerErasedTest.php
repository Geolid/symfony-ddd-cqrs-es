<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Infrastructure\CipherKey;

use Crm\Customer\Application\IntegrationEvent\CustomerErased\CustomerErasedIntegrationEvent;
use Patchlevel\Hydrator\Extension\Cryptography\Cipher\CipherKey;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyNotExists;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shopping\Checkout\Infrastructure\CipherKey\DropCheckoutSessionCipherKeyOnCustomerErased;
use Shopping\Tests\Checkout\Support\Builder\CheckoutSessionBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class DropCheckoutSessionCipherKeyOnCustomerErasedTest extends AbstractIntegrationTestCase
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
        $customerId = Uuid::uuid7()->toString();
        $other = CheckoutSessionBuilder::new()->create();
        $firstAttempt = CheckoutSessionBuilder::new()->withCustomerId($customerId)->staled()->create();
        $secondAttempt = CheckoutSessionBuilder::new()->withCustomerId($customerId)->create();
        $this->store($other, $firstAttempt, $secondAttempt);
        $now = Clock::get()->now();
        foreach ([$other, $firstAttempt, $secondAttempt] as $checkoutSession) {
            $this->cipherKeyStore->store(new CipherKey(
                id: Uuid::uuid7()->toString(),
                subjectId: $checkoutSession->id->toString(),
                key: 'fake-key',
                method: 'aes256',
                createdAt: $now,
            ));
        }

        // When
        $this->trigger(DropCheckoutSessionCipherKeyOnCustomerErased::class, new CustomerErasedIntegrationEvent($customerId, $now));

        // Then
        $otherKey = $this->cipherKeyStore->currentKeyFor($other->id->toString());
        self::assertSame($other->id->toString(), $otherKey->subjectId);
        $this->assertKeyDropped($firstAttempt->id->toString());
        $this->assertKeyDropped($secondAttempt->id->toString());
    }

    private function assertKeyDropped(string $subjectId): void
    {
        try {
            $this->cipherKeyStore->currentKeyFor($subjectId);
            self::fail(\sprintf('Expected the cipher key for "%s" to have been dropped.', $subjectId));
        } catch (CipherKeyNotExists) {
        }
    }
}
