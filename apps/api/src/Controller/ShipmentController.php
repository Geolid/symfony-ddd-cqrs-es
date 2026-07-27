<?php

declare(strict_types=1);

namespace Api\Controller;

use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Query\QueryBusInterface;
use Shared\Application\Query\Result\ListResult;
use Shipping\Shipment\Application\Command\DispatchShipment\DispatchShipment;
use Shipping\Shipment\Application\Finder\Shipment\ShipmentResult;
use Shipping\Shipment\Application\Query\ListShipments\ListShipments;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ShipmentController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
    ) {
    }

    #[Route('/shipments', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        /** @var ListResult<ShipmentResult> $result */
        $result = $this->queryBus->ask(new ListShipments(
            status: $request->query->get('status'),
            page: $request->query->getInt('page', 1),
            itemsPerPage: $request->query->getInt('itemsPerPage', 20),
        ));

        return new JsonResponse([
            'items' => array_map(self::normalize(...), $result->items),
            'pagination' => [
                'totalItems' => $result->pagination->totalItems,
                'currentPage' => $result->pagination->currentPage,
                'itemsPerPage' => $result->pagination->itemsPerPage,
                'lastPage' => $result->pagination->lastPage,
            ],
        ]);
    }

    /**
     * Manual override — in the normal flow a Shipment is created automatically when Ordering
     * publishes its OrderPlacedIntegrationEvent (see Shipping\Shipment\Application\Processor)
     * and moved forward by apps/cli's batch dispatcher or apps/webhook's carrier callback.
     */
    #[Route('/shipments/{id}/dispatch', methods: ['POST'])]
    public function dispatch(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new DispatchShipment($id));

        return new JsonResponse(null, 204);
    }

    /**
     * @return array<string, mixed>
     */
    private static function normalize(ShipmentResult $shipment): array
    {
        return [
            'id' => $shipment->id,
            'orderId' => $shipment->orderId,
            'status' => $shipment->status,
            'createdAt' => $shipment->createdAt->format('c'),
            'dispatchedAt' => $shipment->dispatchedAt?->format('c'),
            'deliveredAt' => $shipment->deliveredAt?->format('c'),
        ];
    }
}
