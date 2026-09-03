<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Policy;

use Fulfilment\Shipment\Application\Command\RequestShipment\RequestShipment;
use Fulfilment\Shipment\Application\Policy\RequestShipmentOnOrderConfirmed;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Fulfilment\Tests\Shipment\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\IntegrationEvent\OrderConfirmed\OrderConfirmedIntegrationEvent;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Command\CommandInterface;
use Shared\Domain\ValueObject\PostalAddress;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class RequestShipmentOnOrderConfirmedTest extends AbstractIntegrationTestCase
{
    private CommandBusInterface&MockObject $commandBus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->replace(CommandBusInterface::class, $this->commandBus);
    }

    #[Test]
    public function itRequests(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $customerId = Uuid::uuid7()->toString();
        $shippingAddress = ShipmentBuilder::sample('shippingAddress');

        $dispatched = null;
        $this->commandBus->expects(self::once())->method('dispatch')
            ->willReturnCallback(static function (CommandInterface $command) use (&$dispatched): void {
                $dispatched = $command;
            });

        // When
        $this->trigger(RequestShipmentOnOrderConfirmed::class, new OrderConfirmedIntegrationEvent(
            orderId: $orderId,
            customerId: $customerId,
            shippingAddress: $this->rawAddress($shippingAddress),
            confirmedAt: Clock::get()->now(),
        ));

        // Then
        self::assertInstanceOf(RequestShipment::class, $dispatched);
        self::assertSame(ShipmentId::forOrder($orderId)->toString(), $dispatched->id);
        self::assertSame($orderId, $dispatched->orderId);
        self::assertSame($customerId, $dispatched->customerId);
        self::assertSame($this->rawAddress($shippingAddress), $dispatched->shippingAddress);
    }

    /**
     * @return array{firstName: string, lastName: string, street: string, postalCode: string, city: string, countryCode: string}
     */
    private function rawAddress(PostalAddress $address): array
    {
        return [
            'firstName' => $address->fullName->firstName,
            'lastName' => $address->fullName->lastName,
            'street' => $address->address->street,
            'postalCode' => $address->address->postalCode,
            'city' => $address->address->city,
            'countryCode' => $address->address->countryCode->value,
        ];
    }
}
