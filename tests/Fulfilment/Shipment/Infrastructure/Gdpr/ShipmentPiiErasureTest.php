<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Infrastructure\Gdpr;

use Fulfilment\Shipment\Domain\Event\ShipmentCreated;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Store\Store;
use PHPUnit\Framework\Attributes\Test;
use Shared\Domain\Gdpr\DataSubjectErasureInterface;
use Shared\Infrastructure\Gdpr\DataSubjectEraser;
use Support\AbstractIntegrationTestCase;

final class ShipmentPiiErasureTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itCryptoShredsTheFrozenAddressOnErasure(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()
            ->withCustomerId('customer-1')
            ->withCustomerAddress('buyer@example.com')
            ->create();
        $this->store($shipment);

        // When
        $this->service(DataSubjectEraser::class)->onEvent(
            Message::create(new DummyDataSubjectErased('customer-1')),
        );

        // Then
        self::assertNull($this->createdEventOf($shipment->id()->toString())->customerAddress);
    }

    private function createdEventOf(string $id): ShipmentCreated
    {
        foreach ($this->service(Store::class)->load() as $message) {
            $event = $message->event();

            if ($event instanceof ShipmentCreated && $event->id === $id) {
                return $event;
            }
        }

        self::fail('ShipmentCreated event not found in the stream.');
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
