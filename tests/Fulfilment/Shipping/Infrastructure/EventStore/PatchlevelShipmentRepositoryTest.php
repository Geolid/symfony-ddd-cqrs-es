<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Infrastructure\EventStore;

use Fulfilment\Shipping\Domain\Exception\ShipmentAlreadyExistsException;
use Fulfilment\Shipping\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipping\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipping\Domain\Shipment;
use Fulfilment\Tests\Shipping\Support\Factory\ShipmentFactory;
use Fulfilment\Tests\Shipping\Support\Factory\ShipmentIdFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Mapper\PostalAddressMapper;
use Support\TestCase\AbstractIntegrationTestCase;

final class PatchlevelShipmentRepositoryTest extends AbstractIntegrationTestCase
{
    private ShipmentRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(ShipmentRepositoryInterface::class);
    }

    #[Test]
    #[DataProvider('provideLifecycle')]
    public function itSavesAndLoads(ShipmentFactory $factory): void
    {
        // Given
        $shipment = $factory->create();

        // When
        $this->repository->save($shipment);
        $loaded = $this->repository->load($shipment->id);

        // Then
        self::assertSame($this->propertiesOf($shipment), $this->propertiesOf($loaded));
    }

    /**
     * @return iterable<string, array{ShipmentFactory}>
     */
    public static function provideLifecycle(): iterable
    {
        yield 'delivered' => [ShipmentFactory::new()->prepared()->manifested()->dispatched()->delivered()->erasureApproved()];
        yield 'cancelled' => [ShipmentFactory::new()->prepared()->cancelled()];
        yield 'cancellation rejected' => [ShipmentFactory::new()->prepared()->manifested()->dispatched()->cancelled()];
    }

    #[Test]
    public function itThrowsWhenAlreadyExists(): void
    {
        // Given
        $shipment = ShipmentFactory::new()
            ->create();
        $this->store($shipment);
        $duplicate = ShipmentFactory::new()
            ->withOrderId($shipment->orderId)
            ->create();

        // Then
        $this->expectException(ShipmentAlreadyExistsException::class);

        // When
        $this->repository->save($duplicate);
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Then
        $this->expectException(ShipmentNotFoundException::class);

        // When
        $this->repository->load(ShipmentIdFactory::new()->create());
    }

    #[Test]
    public function itHas(): void
    {
        // Given
        $shipment = ShipmentFactory::new()->create();
        $this->store($shipment);

        // When
        $exists = $this->repository->has($shipment->id);

        // Then
        self::assertTrue($exists);
    }

    #[Test]
    public function itHasNot(): void
    {
        // When
        $notExists = $this->repository->has(ShipmentIdFactory::new()->create());

        // Then
        self::assertFalse($notExists);
    }

    /**
     * @return array<string, mixed>
     */
    private function propertiesOf(Shipment $shipment): array
    {
        $atom = static fn (?\DateTimeImmutable $date): ?string => $date?->format(\DateTimeInterface::ATOM);

        return [
            'id' => $shipment->id->toString(),
            'orderId' => $shipment->orderId,
            'customerId' => $shipment->customerId,
            'origin' => PostalAddressMapper::toArray($shipment->origin),
            'destination' => PostalAddressMapper::toArray($shipment->destination),
            'createdAt' => $atom($shipment->createdAt),
            'operationalState' => $shipment->operationalState->value,
            'preparedAt' => $atom($shipment->preparedAt),
            'trackingNumber' => $shipment->trackingNumber?->value,
            'manifestedAt' => $atom($shipment->manifestedAt),
            'dispatchedAt' => $atom($shipment->dispatchedAt),
            'deliveredAt' => $atom($shipment->deliveredAt),
            'cancelledAt' => $atom($shipment->cancelledAt),
            'cancellationRejectedAt' => $atom($shipment->cancellationRejectedAt),
            'erasureState' => $shipment->erasureState->value,
            'erasureApprovedAt' => $atom($shipment->erasureApprovedAt),
            'erasedAt' => $atom($shipment->erasedAt),
        ];
    }
}
