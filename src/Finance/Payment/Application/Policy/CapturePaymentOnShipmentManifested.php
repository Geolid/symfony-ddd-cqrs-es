<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Policy;

use Finance\Payment\Application\Command\CapturePayment\CapturePayment;
use Finance\Payment\Domain\ValueObject\PaymentId;
use Fulfilment\Shipping\Application\IntegrationEvent\ShipmentManifested\ShipmentManifestedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('finance.payment.capture_payment_on_shipment_manifested')]
final readonly class CapturePaymentOnShipmentManifested
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(ShipmentManifestedIntegrationEvent::class)]
    public function __invoke(ShipmentManifestedIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new CapturePayment(
            PaymentId::forOrder($event->reference)->toString(),
        ));
    }
}
