<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Pii;

use Iam\Authentication\Domain\ApiKeyCredential\Event\ApiKeyCredentialIssued;
use Iam\Tests\Authentication\Support\Builder\ApiKeyCredentialBuilder;
use Iam\Tests\Authentication\Support\Double\FakeApiKeyHasher;
use Patchlevel\EventSourcing\Serializer\EventSerializer;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use PHPUnit\Framework\Attributes\Test;
use Shared\Domain\Pii\ErasedFieldSentinel;
use Support\TestCase\AbstractIntegrationTestCase;

final class ApiKeyCredentialPiiErasureTest extends AbstractIntegrationTestCase
{
    private CipherKeyStore $cipherKeyStore;

    private EventSerializer $serializer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cipherKeyStore = $this->service(CipherKeyStore::class);
        $this->serializer = $this->service(EventSerializer::class);
    }

    #[Test]
    public function itCryptoShredsLabelOnErasure(): void
    {
        // Given
        $credential = ApiKeyCredentialBuilder::new()->withHasher(new FakeApiKeyHasher())->create();
        $this->store($credential);
        $serialized = $this->serializedEventOf(
            ApiKeyCredentialIssued::class,
            static fn (ApiKeyCredentialIssued $event): bool => $event->id->equals($credential->id),
        );

        // When
        $this->cipherKeyStore->removeWithSubjectId($credential->id->toString());

        // Then
        $rehydrated = $this->serializer->deserialize($serialized);
        self::assertInstanceOf(ApiKeyCredentialIssued::class, $rehydrated);
        $sentinel = new ErasedFieldSentinel('erased-%s');
        self::assertSame($sentinel($credential->id->toString()), $rehydrated->label->value);
    }
}
