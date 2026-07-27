<?php

declare(strict_types=1);

namespace Api\Controller;

use Ordering\Order\Application\Command\CancelOrder\CancelOrder;
use Ordering\Order\Application\Command\PlaceOrder\PlaceOrder;
use Ordering\Order\Application\Finder\Order\OrderResult;
use Ordering\Order\Application\Query\GetOrder\GetOrder;
use Ordering\Order\Application\Query\ListOrders\ListOrders;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Query\QueryBusInterface;
use Shared\Application\Query\Result\ListResult;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * A Delivery Mechanism only ever calls the Command/Query bus — never a BC's Application layer
 * directly (see #[AsDrivingPort], enforced by Tools\PHPat\DeliveryMechanismTest).
 */
final readonly class OrderController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
    ) {
    }

    #[Route('/orders', methods: ['POST'])]
    public function place(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), associative: true, flags: \JSON_THROW_ON_ERROR);

        $id = Uuid::uuid7()->toString();

        $this->commandBus->dispatch(new PlaceOrder(
            id: $id,
            customerId: (string) $payload['customerId'],
            totalAmountInCents: (int) $payload['totalAmountInCents'],
        ));

        return new JsonResponse(['id' => $id], Response::HTTP_CREATED);
    }

    #[Route('/orders/{id}/cancel', methods: ['POST'])]
    public function cancel(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new CancelOrder($id));

        return new JsonResponse(null, 204);
    }

    #[Route('/orders/{id}', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        $order = $this->queryBus->ask(new GetOrder($id));

        return new JsonResponse(self::normalize($order));
    }

    #[Route('/orders', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        /** @var ListResult<OrderResult> $result */
        $result = $this->queryBus->ask(new ListOrders(
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
     * @return array<string, mixed>
     */
    private static function normalize(OrderResult $order): array
    {
        return [
            'id' => $order->id,
            'customerId' => $order->customerId,
            'totalAmountInCents' => $order->totalAmountInCents,
            'status' => $order->status,
            'placedAt' => $order->placedAt->format('c'),
            'cancelledAt' => $order->cancelledAt?->format('c'),
        ];
    }
}
