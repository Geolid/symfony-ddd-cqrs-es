<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Policy;

use Finance\Payment\Application\Command\CapturePayment\CapturePayment;
use Finance\Payment\Application\Command\FailPayment\FailPayment;
use Finance\Payment\Application\PSP\Exception\PaymentFatalFailureException;
use Finance\Payment\Application\PSP\PaymentGatewayInterface;
use Finance\Payment\Application\PSP\PaymentGatewayStatus;
use Finance\Payment\Domain\Repository\PaymentRepositoryInterface;
use Finance\Payment\Domain\ValueObject\PaymentId;
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
        private PaymentRepositoryInterface $repository,
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
        $id = PaymentId::forOrder($event->reference);

        if (!$this->repository->has($id)) {
            return;
        }

        $payment = $this->repository->load($id);

        $command = match ($this->paymentGateway->capture($payment->reference->value)) {
            PaymentGatewayStatus::CAPTURED => new CapturePayment($id->toString()),
            PaymentGatewayStatus::DECLINED => new FailPayment($id->toString()),
            default => null,
        };

        if ($command instanceof CommandInterface) {
            $this->commandBus->dispatch($command);
        }
    }

    /**
     * A rejected capture request will never succeed by retrying as-is (a malformed
     * payload, a declined reference) — it's marked failed the same way a gateway-side
     * decline already is, instead of leaving the Payment stuck AUTHORIZED forever.
     *
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

        $this->commandBus->dispatch(new FailPayment(PaymentId::forOrder($event->reference)->toString()));
    }
}
