<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Policy;

use Finance\Payment\Application\Command\AbandonPayment\AbandonPayment;
use Finance\Payment\Domain\Repository\PaymentRepositoryInterface;
use Finance\Payment\Domain\ValueObject\PaymentId;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;
use Shopping\Checkout\Application\IntegrationEvent\CheckoutSessionStaled\CheckoutSessionStaledIntegrationEvent;

#[Policy('finance.payment.abandon_payment_on_checkout_session_staled')]
final readonly class AbandonPaymentOnCheckoutSessionStaled
{
    public function __construct(
        private PaymentRepositoryInterface $repository,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(CheckoutSessionStaledIntegrationEvent::class)]
    public function __invoke(CheckoutSessionStaledIntegrationEvent $event): void
    {
        $id = PaymentId::forCheckoutSession($event->checkoutSessionId);

        if (!$this->repository->has($id)) {
            return;
        }

        $this->commandBus->dispatch(new AbandonPayment($id->toString()));
    }
}
