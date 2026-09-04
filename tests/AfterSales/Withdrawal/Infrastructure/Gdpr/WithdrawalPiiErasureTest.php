<?php

declare(strict_types=1);

namespace AfterSales\Tests\Withdrawal\Infrastructure\Gdpr;

use AfterSales\Tests\Withdrawal\Support\Builder\WithdrawalBuilder;
use AfterSales\Withdrawal\Domain\Event\WithdrawalRequested;
use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Serializer\EventSerializer;
use PHPUnit\Framework\Attributes\Test;
use Shared\Infrastructure\Gdpr\DataSubjectEraserProcessor;
use Shared\Tests\Support\Double\StubDataSubjectErased;
use Support\TestCase\AbstractIntegrationTestCase;

final class WithdrawalPiiErasureTest extends AbstractIntegrationTestCase
{
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
        ($this->service(DataSubjectEraserProcessor::class))(
            Message::create(new StubDataSubjectErased($builder['customerId'])),
        );

        // Then
        $rehydrated = $this->service(EventSerializer::class)->deserialize($serialized);
        self::assertInstanceOf(WithdrawalRequested::class, $rehydrated);
        $erasedAddress = ['recipientName' => 'erased', 'street' => 'erased', 'postalCode' => '00000', 'city' => 'erased', 'countryCode' => 'ZZ'];
        self::assertSame($erasedAddress, $rehydrated->shippingAddress);
    }
}
