<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Infrastructure\Gdpr;

use Fulfilment\Shipping\Domain\Event\ShipmentRequested;
use Fulfilment\Tests\Shipping\Support\Builder\ShipmentBuilder;
use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Serializer\EventSerializer;
use PHPUnit\Framework\Attributes\Test;
use Shared\Infrastructure\Gdpr\DataSubjectEraserProcessor;
use Shared\Tests\Support\Double\StubDataSubjectErased;
use Support\TestCase\AbstractIntegrationTestCase;

final class ShipmentPiiErasureTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itCryptoShredsFrozenAddressesOnErasure(): void
    {
        // Given
        $builder = ShipmentBuilder::new();
        $shipment = $builder->create();
        $this->store($shipment);
        $serialized = $this->serializedEventOf(
            ShipmentRequested::class,
            static fn (ShipmentRequested $event): bool => $event->id === $shipment->id->toString(),
        );

        // When
        ($this->service(DataSubjectEraserProcessor::class))(
            Message::create(new StubDataSubjectErased($builder['buyerId'])),
        );

        // Then
        $rehydrated = $this->service(EventSerializer::class)->deserialize($serialized);
        self::assertInstanceOf(ShipmentRequested::class, $rehydrated);
        $erasedAddress = ['recipientName' => 'erased', 'street' => 'erased', 'postalCode' => '00000', 'city' => 'erased', 'countryCode' => 'ZZ'];
        self::assertSame($erasedAddress, $rehydrated->origin);
        self::assertSame($erasedAddress, $rehydrated->destination);
    }
}
