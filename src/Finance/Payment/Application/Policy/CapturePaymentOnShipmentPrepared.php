<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Policy;

use Finance\Payment\Application\Command\CapturePayment\CapturePayment;
use Finance\Payment\Application\Command\FailPayment\FailPayment;
use Finance\Payment\Application\Finder\Payment\Exception\PaymentResultNotFoundException;
use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\PSP\Exception\PaymentFatalFailureException;
use Finance\Payment\Application\PSP\PaymentGatewayInterface;
use Finance\Payment\Application\PSP\PaymentGatewayStatus;
use Fulfilment\Shipping\Application\IntegrationEvent\ShipmentPrepared\ShipmentPreparedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\OnFailed;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Message\Message;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Command\CommandInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('finance.payment.capture_payment_on_shipment_prepared')]
final readonly class CapturePaymentOnShipmentPrepared
{
    public function __construct(
        private PaymentFinderInterface $paymentFinder,
        private PaymentGatewayInterface $paymentGateway,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(ShipmentPreparedIntegrationEvent::class)]
    public function __invoke(ShipmentPreparedIntegrationEvent $event): void
    {
        try {
            $payment = $this->paymentFinder->ofOrderId($event->orderId);
        } catch (PaymentResultNotFoundException) {
            return;
        }

        $command = match ($this->paymentGateway->capture($payment->reference)) {
            PaymentGatewayStatus::CAPTURED => new CapturePayment($payment->id, $event->orderId),
            PaymentGatewayStatus::DECLINED => new FailPayment($payment->id, $event->orderId),
            default => null,
        };

        if ($command instanceof CommandInterface) {
            $this->commandBus->dispatch($command);
        }
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[OnFailed]
    public function onGatewayFailure(Message $message, \Throwable $error): void
    {
        if (!$error instanceof PaymentFatalFailureException) {
            throw $error;
        }

        $event = $message->event();
        \assert($event instanceof ShipmentPreparedIntegrationEvent);

        $payment = $this->paymentFinder->ofOrderId($event->orderId);

        $this->commandBus->dispatch(new FailPayment($payment->id, $event->orderId));
    }
}
