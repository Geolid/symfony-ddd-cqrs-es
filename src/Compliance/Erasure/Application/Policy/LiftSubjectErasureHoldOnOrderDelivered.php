<?php

declare(strict_types=1);

namespace Compliance\Erasure\Application\Policy;

use Compliance\Erasure\Application\Command\LiftSubjectErasureHold\LiftSubjectErasureHold;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\IntegrationEvent\OrderDelivered\OrderDeliveredIntegrationEvent;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('compliance.erasure.lift_subject_erasure_hold_on_order_delivered')]
final readonly class LiftSubjectErasureHoldOnOrderDelivered
{
    private const string SOURCE_TYPE = 'sales.order.order';

    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(OrderDeliveredIntegrationEvent::class)]
    public function __invoke(OrderDeliveredIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new LiftSubjectErasureHold(
            subjectId: $event->buyerId,
            sourceType: self::SOURCE_TYPE,
            sourceId: $event->orderId,
        ));
    }
}
