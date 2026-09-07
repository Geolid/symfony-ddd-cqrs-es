<?php

declare(strict_types=1);

namespace AfterSales\Tests\Return\Infrastructure\Gdpr;

use AfterSales\Return\Application\IntegrationEvent\WithdrawalRequested\WithdrawalRequestedIntegrationEvent;
use AfterSales\Return\Domain\Event\WithdrawalRequested;
use AfterSales\Tests\Return\Support\Builder\WithdrawalBuilder;
use Patchlevel\EventSourcing\Serializer\EventSerializer;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use PHPUnit\Framework\Attributes\Test;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Support\TestCase\AbstractIntegrationTestCase;

final class WithdrawalPiiErasureTest extends AbstractIntegrationTestCase
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
    public function itCryptoShredsShippingAddressOnErasure(): void
    {
        // Given
        $builder = WithdrawalBuilder::new();
        $withdrawal = $builder->create();
        $this->store($withdrawal);
        $serialized = $this->serializedEventOf(
            WithdrawalRequested::class,
            static fn (WithdrawalRequested $event): bool => $event->id === $withdrawal->id->toString(),
        );

        // When
        $this->cipherKeyStore->removeWithSubjectId($builder['buyerId']);

        // Then
        $rehydrated = $this->serializer->deserialize($serialized);
        self::assertInstanceOf(WithdrawalRequested::class, $rehydrated);
        self::assertSame($this->erasedPostalAddress()->toArray(), $rehydrated->shippingAddress->toArray());
    }

    #[Test]
    public function itCryptoShredsWithdrawalRequestedIntegrationEventShippingAddressOnErasure(): void
    {
        // Given
        $builder = WithdrawalBuilder::new();
        $withdrawal = $builder->create();
        $this->store($withdrawal);
        $serialized = $this->serializedEventOf(
            WithdrawalRequestedIntegrationEvent::class,
            static fn (WithdrawalRequestedIntegrationEvent $event): bool => $event->withdrawalId === $withdrawal->id->toString(),
        );

        // When
        $this->cipherKeyStore->removeWithSubjectId($builder['buyerId']);

        // Then
        $rehydrated = $this->serializer->deserialize($serialized);
        self::assertInstanceOf(WithdrawalRequestedIntegrationEvent::class, $rehydrated);
        self::assertSame($this->erasedPostalAddress()->toArray(), $rehydrated->shippingAddress);
    }

    private function erasedPostalAddress(): PostalAddress
    {
        return PostalAddress::of('erased', Address::of('erased', '00000', 'erased', 'ZZ'));
    }
}
