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
use Web\Controller\Criteria\OrderCriteria;
use Web\Form\FormData\PlaceOrderFormData;
use Web\Form\PlaceOrderType;

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
    #[Route('/', name: 'orders_home', methods: ['GET'])]
    #[Route('/orders', name: 'orders_index', methods: ['GET'])]
    public function index(
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        OrderCriteria $criteria = new OrderCriteria(),
    ): Response {
        $orders = $this->queryBus->ask(new ListOrders(
            page: $criteria->page,
            itemsPerPage: $criteria->itemsPerPage,
        ));

        return $this->render('orders/index.html.twig', [
            'orders' => $orders,
            'cancellableStatus' => PublishedOrderStatus::PLACED->value,
        ]);
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route('/orders/new', name: 'orders_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $formData = new PlaceOrderFormData();
        $form = $this->createForm(PlaceOrderType::class, $formData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->dispatch(new PlaceOrder(
                id: Uuid::uuid7()->toString(),
                customerId: (string) $formData->customerId,
                totalAmountInCents: (int) $formData->totalAmountInCents,
            ));

            return $this->redirectToRoute('orders_index');
        }

        return $this->render('orders/new.html.twig', ['form' => $form]);
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route('/orders/{id}/cancel', methods: ['POST'])]
    public function cancel(Request $request, string $id): Response
    {
        if (!$this->isCsrfTokenValid('cancel-order-'.$id, (string) $request->request->get('_token'))) {
            throw new BadRequestHttpException('Invalid CSRF token.');
        }

        $this->commandBus->dispatch(new CancelOrder($id));

        return $this->redirectToRoute('orders_index');
    }
}
