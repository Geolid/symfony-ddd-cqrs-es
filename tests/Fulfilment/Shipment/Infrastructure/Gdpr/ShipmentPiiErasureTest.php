<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Infrastructure\Gdpr;

use Fulfilment\Shipment\Domain\Event\ShipmentCreated;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Serializer\EventSerializer;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Domain\Gdpr\DataSubjectErasureInterface;
use Shared\Infrastructure\Gdpr\DataSubjectEraser;
use Support\AbstractIntegrationTestCase;

final class ShipmentPiiErasureTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itCryptoShredsTheFrozenAddressOnErasure(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $shipment = ShipmentTestFactory::new()
            ->withCustomerId($customerId)
            ->store();
        $serialized = $this->serializedEventOf(
            ShipmentCreated::class,
            static fn (ShipmentCreated $event): bool => $event->id === $shipment->id()->toString(),
        );

        // When
        $this->service(DataSubjectEraser::class)->onEvent(
            Message::create(new DummyDataSubjectErased($customerId)),
        );

        // Then
        $rehydrated = $this->service(EventSerializer::class)->deserialize($serialized);
        self::assertInstanceOf(ShipmentCreated::class, $rehydrated);
        self::assertSame(
            ['firstName' => 'erased', 'lastName' => 'erased', 'street' => 'erased', 'postalCode' => '00000', 'city' => 'erased'],
            $rehydrated->shippingAddress,
        );
    }
}

final readonly class DummyDataSubjectErased implements DataSubjectErasureInterface
{
    public function __construct(private string $customerId)
    {
    }

    public function subjectId(): string
    {
        return $this->customerId;
    }
}
