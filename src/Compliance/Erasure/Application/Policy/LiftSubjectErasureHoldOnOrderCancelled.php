<?php

declare(strict_types=1);

namespace Compliance\Erasure\Application\Policy;

use Compliance\Erasure\Application\Command\LiftSubjectErasureHold\LiftSubjectErasureHold;
use Compliance\Erasure\Application\Finder\Buyer\BuyerFinderInterface;
use Compliance\Erasure\Domain\ValueObject\SubjectId;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\IntegrationEvent\OrderCancelled\OrderCancelledIntegrationEvent;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('compliance.erasure.lift_subject_erasure_hold_on_order_cancelled')]
final readonly class LiftSubjectErasureHoldOnOrderCancelled
{
    private const string SOURCE_TYPE = 'sales.order.order';

    public function __construct(
        private BuyerFinderInterface $buyerFinder,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(OrderCancelledIntegrationEvent::class)]
    public function __invoke(OrderCancelledIntegrationEvent $event): void
    {
        $buyer = $this->buyerFinder->ofIdOrNull($event->buyerId);

        if (null === $buyer) {
            return;
        }

        $this->commandBus->dispatch(new LiftSubjectErasureHold(
            subjectId: SubjectId::forIdentity($buyer->identityId)->toString(),
            sourceType: self::SOURCE_TYPE,
            sourceId: $event->orderId,
        ));
    }
}
