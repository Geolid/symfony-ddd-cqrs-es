<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Pii;

use Iam\Authentication\Domain\ApiKeyCredential\Event\ApiKeyCredentialIssued;
use Iam\Tests\Authentication\Support\Double\FakeApiKeyHasher;
use Iam\Tests\Authentication\Support\Factory\ApiKeyCredentialFactory;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use PHPUnit\Framework\Attributes\Test;
use Shared\Domain\Pii\ErasedLabel;
use Support\TestCase\AbstractIntegrationTestCase;

final class ApiKeyCredentialPiiErasureTest extends AbstractIntegrationTestCase
{
    private CipherKeyStore $cipherKeyStore;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cipherKeyStore = $this->service(CipherKeyStore::class);
    }

    #[Test]
    public function itCryptoShredsLabelOnErasure(): void
    {
        // Given
        $credential = ApiKeyCredentialFactory::new()->withHasher(new FakeApiKeyHasher())->create();
        $this->store($credential);

        // When
        $this->cipherKeyStore->removeWithSubjectId($credential->id->toString());

        // Then
        $erased = $this->storedEventOf(ApiKeyCredentialIssued::class, $credential->id->toString());
        self::assertSame((new ErasedLabel())($credential->id->toString())->value, $erased->label->value);
    }
}
