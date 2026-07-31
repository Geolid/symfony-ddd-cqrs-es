<?php

declare(strict_types=1);

namespace Web\Controller;

use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Command\CancelOrder\CancelOrder;
use Sales\Order\Application\Command\PlaceOrder\PlaceOrder;
use Sales\Order\Application\Language\PublishedOrderStatus;
use Sales\Order\Application\Query\ListOrders\ListOrders;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Web\Controller\Criteria\OrderCriteria;
use Web\Form\FormData\OrderLineFormData;
use Web\Form\FormData\PlaceOrderFormData;
use Web\Form\PlaceOrderType;

#[Route('/sales/orders')]
final class OrderController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     */
    #[Route(name: 'sales_order_list', methods: ['GET'])]
    public function list(
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        OrderCriteria $criteria = new OrderCriteria(),
    ): Response {
        $orders = $this->queryBus->ask(new ListOrders(
            page: $criteria->page,
            itemsPerPage: $criteria->itemsPerPage,
        ));

        return $this->render('sales/order/list.html.twig', [
            'orders' => $orders,
            'cancellableStatus' => PublishedOrderStatus::PLACED->value,
        ]);
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route('/place', name: 'sales_order_place', methods: ['GET', 'POST'])]
    public function place(Request $request): Response
    {
        $formData = new PlaceOrderFormData();
        $form = $this->createForm(PlaceOrderType::class, $formData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->dispatch(new PlaceOrder(
                id: Uuid::uuid7()->toString(),
                customerId: (string) $formData->customerId,
                lines: array_values(array_map(
                    static fn (OrderLineFormData $line): array => [
                        'label' => (string) $line->label,
                        'quantity' => (int) $line->quantity,
                        'unitAmountInCents' => (int) $line->unitAmountInCents,
                    ],
                    $formData->lines,
                )),
            ));

            return $this->redirectToRoute('sales_order_list');
        }

        return $this->render('sales/order/place.html.twig', ['form' => $form]);
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route('/{id}/cancel', name: 'sales_order_cancel', methods: ['POST'], requirements: ['id' => Requirement::UUID])]
    public function cancel(Request $request, string $id): Response
    {
        if (!$this->isCsrfTokenValid('cancel-order-'.$id, (string) $request->request->get('_token'))) {
            throw new BadRequestHttpException('Invalid CSRF token.');
        }

        $this->commandBus->dispatch(new CancelOrder($id));

        return $this->redirectToRoute('sales_order_list');
    }
}
