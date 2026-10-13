<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Pii;

use Iam\Identity\Domain\Repository\IdentityRepositoryInterface;
use Iam\Tests\Identity\Support\Factory\IdentityFactory;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class IdentityPiiErasureTest extends AbstractIntegrationTestCase
{
    private CipherKeyStore $cipherKeyStore;

    private IdentityRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cipherKeyStore = $this->service(CipherKeyStore::class);
        $this->repository = $this->service(IdentityRepositoryInterface::class);
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
        self::assertSame('erased', $this->repository->load($identity->id)->suspensionReason?->value);
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
        self::assertSame('erased', $this->repository->load($identity->id)->reactivationReason?->value);
    }
}
