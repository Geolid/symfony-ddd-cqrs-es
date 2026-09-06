<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Policy;

use Finance\Payment\Application\Command\RejectPaymentRefund\RejectPaymentRefund;
use Finance\Payment\Application\PSP\Exception\PaymentFatalFailureException;
use Finance\Payment\Application\PSP\Exception\PaymentGatewayException;
use Finance\Payment\Application\PSP\PaymentGatewayInterface;
use Finance\Payment\Domain\Event\PaymentRefundRequired;
use Patchlevel\EventSourcing\Attribute\OnFailed;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Message\Message;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('finance.payment.refund_payment_on_payment_refund_required')]
final readonly class RefundPaymentOnPaymentRefundRequired
{
    public function __construct(
        private PaymentGatewayInterface $paymentGateway,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws PaymentGatewayException
     */
    #[Subscribe(PaymentRefundRequired::class)]
    public function __invoke(PaymentRefundRequired $event): void
    {
        $this->paymentGateway->refund($event->reference->value);
    }

    /**
     * A refund request Globex rejects outright will never succeed by retrying as-is —
     * recorded on the Payment itself so Finance.Refund's own tracked Refund gets marked
     * failed too, instead of staying INITIATED with the money never actually returned.
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
        \assert($event instanceof PaymentRefundRequired);

        $this->commandBus->dispatch(new RejectPaymentRefund($event->id));
    }
}
