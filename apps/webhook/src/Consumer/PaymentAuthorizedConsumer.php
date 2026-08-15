<?php

declare(strict_types=1);

namespace Webhook\Consumer;

use Sales\Order\Application\Command\AuthorizeOrderPayment\AuthorizeOrderPayment;
use Sales\Order\Application\Query\GetOrderPaymentByReference\GetOrderPaymentByReference;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Symfony\Component\RemoteEvent\Attribute\AsRemoteEventConsumer;
use Symfony\Component\RemoteEvent\Consumer\ConsumerInterface;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Webhook\Webhook\PaymentAuthorizedParser;

#[AsRemoteEventConsumer(PaymentAuthorizedParser::EVENT_TYPE)]
final readonly class PaymentAuthorizedConsumer implements ConsumerInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function consume(RemoteEvent $event): void
    {
        $payload = $event->getPayload();

        $orderPayment = $this->queryBus->ask(new GetOrderPaymentByReference($payload['paymentReference']));

        $this->commandBus->dispatch(new AuthorizeOrderPayment($orderPayment->id));
    }
}
