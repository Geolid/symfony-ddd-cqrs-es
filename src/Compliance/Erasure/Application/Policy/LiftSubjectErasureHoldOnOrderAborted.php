<?php

declare(strict_types=1);

namespace Compliance\Erasure\Application\Policy;

use Compliance\Erasure\Application\Command\LiftSubjectErasureHold\LiftSubjectErasureHold;
use Compliance\Erasure\Application\Finder\Buyer\BuyerFinderInterface;
use Compliance\Erasure\Domain\ValueObject\SubjectId;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\IntegrationEvent\OrderAborted\OrderAbortedIntegrationEvent;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('compliance.erasure.lift_subject_erasure_hold_on_order_aborted')]
final readonly class LiftSubjectErasureHoldOnOrderAborted
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
    #[Subscribe(OrderAbortedIntegrationEvent::class)]
    public function __invoke(OrderAbortedIntegrationEvent $event): void
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
