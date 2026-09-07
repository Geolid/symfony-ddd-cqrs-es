<?php

declare(strict_types=1);

namespace Compliance\Erasure\Application\Policy;

use Compliance\Erasure\Application\Command\PlaceSubjectErasureHold\PlaceSubjectErasureHold;
use Compliance\Erasure\Application\Finder\Buyer\BuyerFinderInterface;
use Compliance\Erasure\Domain\ValueObject\SubjectId;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\IntegrationEvent\OrderPlaced\OrderPlacedIntegrationEvent;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('compliance.erasure.place_subject_erasure_hold_on_order_placed')]
final readonly class PlaceSubjectErasureHoldOnOrderPlaced
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
    #[Subscribe(OrderPlacedIntegrationEvent::class)]
    public function __invoke(OrderPlacedIntegrationEvent $event): void
    {
        $buyer = $this->buyerFinder->ofIdOrNull($event->buyerId);

        if (null === $buyer) {
            return;
        }

        $this->commandBus->dispatch(new PlaceSubjectErasureHold(
            subjectId: SubjectId::forIdentity($buyer->identityId)->toString(),
            sourceType: self::SOURCE_TYPE,
            sourceId: $event->orderId,
        ));
    }
}
