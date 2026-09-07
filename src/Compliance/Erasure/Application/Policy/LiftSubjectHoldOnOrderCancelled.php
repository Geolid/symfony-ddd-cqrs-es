<?php

declare(strict_types=1);

namespace Compliance\Erasure\Application\Policy;

use Compliance\Erasure\Application\Command\LiftSubjectHold\LiftSubjectHold;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\IntegrationEvent\OrderCancelled\OrderCancelledIntegrationEvent;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('compliance.erasure.lift_subject_hold_on_order_cancelled')]
final readonly class LiftSubjectHoldOnOrderCancelled
{
    private const string SOURCE_TYPE = 'sales.order.order';

    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(OrderCancelledIntegrationEvent::class)]
    public function __invoke(OrderCancelledIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new LiftSubjectHold(
            subjectId: $event->buyerId,
            sourceType: self::SOURCE_TYPE,
            sourceId: $event->orderId,
        ));
    }
}
