<?php

declare(strict_types=1);

namespace AfterSales\Tests\Return\Infrastructure\Gdpr;

use AfterSales\Return\Application\IntegrationEvent\WithdrawalRequested\WithdrawalRequestedIntegrationEvent;
use AfterSales\Return\Domain\Event\WithdrawalRequested;
use AfterSales\Tests\Return\Support\Builder\WithdrawalBuilder;
use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Serializer\EventSerializer;
use PHPUnit\Framework\Attributes\Test;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Shared\Infrastructure\Gdpr\DataSubjectEraserProcessor;
use Shared\Tests\Support\Double\StubDataSubjectErased;
use Support\TestCase\AbstractIntegrationTestCase;

final class WithdrawalPiiErasureTest extends AbstractIntegrationTestCase
{
    private DataSubjectEraserProcessor $eraser;

    private EventSerializer $serializer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eraser = $this->service(DataSubjectEraserProcessor::class);
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
        ($this->eraser)(Message::create(new StubDataSubjectErased($builder['buyerId'])));

        // Then
        $rehydrated = $this->serializer->deserialize($serialized);
        self::assertInstanceOf(WithdrawalRequested::class, $rehydrated);
        $erasedAddress = PostalAddress::of('erased', Address::of('erased', '00000', 'erased', 'ZZ'));
        self::assertSame($erasedAddress->toArray(), $rehydrated->shippingAddress->toArray());
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
        ($this->eraser)(Message::create(new StubDataSubjectErased($builder['buyerId'])));

        // Then
        $rehydrated = $this->serializer->deserialize($serialized);
        self::assertInstanceOf(WithdrawalRequestedIntegrationEvent::class, $rehydrated);
        $erasedAddress = PostalAddress::of('erased', Address::of('erased', '00000', 'erased', 'ZZ'));
        self::assertSame($erasedAddress->toArray(), $rehydrated->shippingAddress);
    }
}
