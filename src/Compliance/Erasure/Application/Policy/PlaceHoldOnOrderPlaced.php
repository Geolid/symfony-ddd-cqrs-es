<?php

declare(strict_types=1);

namespace Compliance\Erasure\Application\Policy;

use Compliance\Erasure\Application\Command\PlaceHold\PlaceHold;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\IntegrationEvent\OrderPlaced\OrderPlacedIntegrationEvent;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('compliance.erasure.place_hold_on_order_placed')]
final readonly class PlaceHoldOnOrderPlaced
{
    private const string SOURCE_TYPE = 'sales.order.order';

    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(OrderPlacedIntegrationEvent::class)]
    public function __invoke(OrderPlacedIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new PlaceHold(
            subjectId: $event->buyerId,
            sourceType: self::SOURCE_TYPE,
            sourceId: $event->orderId,
        ));
    }
}
