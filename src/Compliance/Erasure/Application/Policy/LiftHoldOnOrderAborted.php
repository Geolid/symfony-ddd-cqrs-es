<?php

declare(strict_types=1);

namespace Compliance\Erasure\Application\Policy;

use Compliance\Erasure\Application\Command\LiftHold\LiftHold;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\IntegrationEvent\OrderAborted\OrderAbortedIntegrationEvent;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('compliance.erasure.lift_hold_on_order_aborted')]
final readonly class LiftHoldOnOrderAborted
{
    private const string SOURCE_TYPE = 'sales.order.order';

    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(OrderAbortedIntegrationEvent::class)]
    public function __invoke(OrderAbortedIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new LiftHold(
            subjectId: $event->buyerId,
            sourceType: self::SOURCE_TYPE,
            sourceId: $event->orderId,
        ));
    }
}
