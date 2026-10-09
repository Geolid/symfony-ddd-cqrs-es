<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Pii;

use Iam\Authentication\Domain\ApiKeyCredential\Repository\ApiKeyCredentialRepositoryInterface;
use Iam\Tests\Authentication\Support\Double\FakeApiKeyHasher;
use Iam\Tests\Authentication\Support\Factory\ApiKeyCredentialFactory;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use PHPUnit\Framework\Attributes\Test;
use Shared\Domain\Pii\ErasedFieldSentinel;
use Support\TestCase\AbstractIntegrationTestCase;

final class ApiKeyCredentialPiiErasureTest extends AbstractIntegrationTestCase
{
    private CipherKeyStore $cipherKeyStore;

    private ApiKeyCredentialRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cipherKeyStore = $this->service(CipherKeyStore::class);
        $this->repository = $this->service(ApiKeyCredentialRepositoryInterface::class);
    }

    #[Test]
    public function itCryptoShredsLabelOnErasure(): void
    {
        // Given
        $credential = ApiKeyCredentialFactory::new()->withHasher(new FakeApiKeyHasher())->create();
        $this->repository->save($credential);

        // When
        $this->cipherKeyStore->removeWithSubjectId($credential->id->toString());

        // Then
        $sentinel = new ErasedFieldSentinel('erased-%s');
        self::assertSame($sentinel($credential->id->toString()), $this->repository->load($credential->id)->label->value);
    }
}
