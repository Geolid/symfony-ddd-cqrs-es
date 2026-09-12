<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Pii;

use Iam\Authentication\Domain\PasswordCredential\Event\PasswordCredentialDefined;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Iam\Tests\Authentication\Support\Double\FakePasswordHasher;
use Iam\Tests\Authentication\Support\Double\StubPasswordStrength;
use Patchlevel\EventSourcing\Serializer\EventSerializer;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use PHPUnit\Framework\Attributes\Test;
use Shared\Domain\Pii\ErasedFieldSentinel;
use Support\TestCase\AbstractIntegrationTestCase;

final class PasswordCredentialPiiErasureTest extends AbstractIntegrationTestCase
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
    public function itCryptoShredsLoginOnErasure(): void
    {
        // Given
        $credential = PasswordCredentialBuilder::new()
            ->withPasswordStrength(new StubPasswordStrength())
            ->withHasher(new FakePasswordHasher())
            ->create();
        $this->store($credential);
        $serialized = $this->serializedEventOf(
            PasswordCredentialDefined::class,
            static fn (PasswordCredentialDefined $event): bool => $event->id->equals($credential->id),
        );

        // When
        $this->cipherKeyStore->removeWithSubjectId($credential->id->toString());

        // Then
        $rehydrated = $this->serializer->deserialize($serialized);
        self::assertInstanceOf(PasswordCredentialDefined::class, $rehydrated);
        $sentinel = new ErasedFieldSentinel('erased-%s');
        self::assertSame($sentinel($credential->id->toString()), $rehydrated->login->value);
    }
}
