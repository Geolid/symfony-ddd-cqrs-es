<?php

declare(strict_types=1);

namespace Crm\Tests\Customer\Infrastructure\CipherKey;

use Crm\Customer\Domain\Customer\Event\CustomerErased;
use Crm\Customer\Domain\Customer\ValueObject\CustomerId;
use Crm\Customer\Infrastructure\CipherKey\DropCipherKeyOnCustomerErased;
use Crm\Tests\Customer\Support\Builder\CustomerBuilder;
use Patchlevel\Hydrator\Extension\Cryptography\Cipher\CipherKey;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyNotExists;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class DropCipherKeyOnCustomerErasedTest extends AbstractIntegrationTestCase
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
        $customer = CustomerBuilder::new()->create();
        $this->store($customer);
        $customerId = $customer->id->toString();
        $now = Clock::get()->now();
        $this->cipherKeyStore->store(new CipherKey(
            id: Uuid::uuid7()->toString(),
            subjectId: $customerId,
            key: 'fake-key',
            method: 'aes256',
            createdAt: $now,
        ));

        // When
        $this->trigger(DropCipherKeyOnCustomerErased::class, new CustomerErased(CustomerId::fromString($customerId), $now));

        // Then
        $this->expectException(CipherKeyNotExists::class);
        $this->cipherKeyStore->currentKeyFor($customerId);
    }
}
