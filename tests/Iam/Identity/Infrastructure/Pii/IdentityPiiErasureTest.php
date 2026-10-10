<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Pii;

use Iam\Identity\Domain\Event\IdentityReactivated;
use Iam\Identity\Domain\Event\IdentitySuspended;
use Iam\Identity\Domain\Pii\ErasedReason;
use Iam\Tests\Identity\Support\Factory\IdentityFactory;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class IdentityPiiErasureTest extends AbstractIntegrationTestCase
{
    private CipherKeyStore $cipherKeyStore;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cipherKeyStore = $this->service(CipherKeyStore::class);
    }

    #[Test]
    public function itCryptoShredsSuspensionReasonOnErasure(): void
    {
        // Given
        $identity = IdentityFactory::new()->confirmed()->suspended()->create();
        $this->store($identity);

        // When
        $this->cipherKeyStore->removeWithSubjectId($identity->id->toString());

        // Then
        $erased = $this->storedEventOf(IdentitySuspended::class, $identity->id->toString());
        self::assertSame((new ErasedReason())($identity->id->toString())->value, $erased->reason->value);
    }

    #[Test]
    public function itCryptoShredsReactivationReasonOnErasure(): void
    {
        // Given
        $identity = IdentityFactory::new()->confirmed()->suspended()->reactivated()->create();
        $this->store($identity);

        // When
        $this->cipherKeyStore->removeWithSubjectId($identity->id->toString());

        // Then
        $erased = $this->storedEventOf(IdentityReactivated::class, $identity->id->toString());
        self::assertSame((new ErasedReason())($identity->id->toString())->value, $erased->reason->value);
    }
}
