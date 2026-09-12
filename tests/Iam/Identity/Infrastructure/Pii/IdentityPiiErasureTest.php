<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Pii;

use Iam\Identity\Domain\Event\IdentityReactivated;
use Iam\Identity\Domain\Event\IdentitySuspended;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use Patchlevel\EventSourcing\Serializer\EventSerializer;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class IdentityPiiErasureTest extends AbstractIntegrationTestCase
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
    public function itCryptoShredsSuspensionReasonOnErasure(): void
    {
        // Given
        $identity = IdentityBuilder::new()->suspended()->create();
        $this->store($identity);
        $serialized = $this->serializedEventOf(
            IdentitySuspended::class,
            static fn (IdentitySuspended $event): bool => $event->id->equals($identity->id),
        );

        // When
        $this->cipherKeyStore->removeWithSubjectId($identity->id->toString());

        // Then
        $rehydrated = $this->serializer->deserialize($serialized);
        self::assertInstanceOf(IdentitySuspended::class, $rehydrated);
        self::assertSame('erased', $rehydrated->reason->value);
    }

    #[Test]
    public function itCryptoShredsReactivationReasonOnErasure(): void
    {
        // Given
        $identity = IdentityBuilder::new()->suspended()->reactivated()->create();
        $this->store($identity);
        $serialized = $this->serializedEventOf(
            IdentityReactivated::class,
            static fn (IdentityReactivated $event): bool => $event->id->equals($identity->id),
        );

        // When
        $this->cipherKeyStore->removeWithSubjectId($identity->id->toString());

        // Then
        $rehydrated = $this->serializer->deserialize($serialized);
        self::assertInstanceOf(IdentityReactivated::class, $rehydrated);
        self::assertSame('erased', $rehydrated->reason->value);
    }
}
