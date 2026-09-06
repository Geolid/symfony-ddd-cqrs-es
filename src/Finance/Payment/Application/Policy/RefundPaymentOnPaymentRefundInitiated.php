<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Policy;

use Finance\Payment\Application\Command\FailPaymentRefund\FailPaymentRefund;
use Finance\Payment\Application\PSP\Exception\PaymentFatalFailureException;
use Finance\Payment\Application\PSP\Exception\PaymentGatewayException;
use Finance\Payment\Application\PSP\PaymentGatewayInterface;
use Finance\Payment\Domain\Event\PaymentRefundInitiated;
use Patchlevel\EventSourcing\Attribute\OnFailed;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Message\Message;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('finance.payment.refund_payment_on_payment_refund_initiated')]
final readonly class RefundPaymentOnPaymentRefundInitiated
{
    public function __construct(
        private PaymentGatewayInterface $paymentGateway,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws PaymentGatewayException
     */
    #[Subscribe(PaymentRefundInitiated::class)]
    public function __invoke(PaymentRefundInitiated $event): void
    {
        $this->paymentGateway->refund($event->reference->value);
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
        \assert($event instanceof PaymentRefundInitiated);

        $this->commandBus->dispatch(new FailPaymentRefund($event->id, $event->refundId));
    }
}
