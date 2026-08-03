<?php

declare(strict_types=1);

namespace Web\Controller;

use Catalog\Product\Application\Finder\Product\ProductResult;
use Catalog\Product\Application\Query\ListProducts\ListProducts;
use Ramsey\Uuid\Uuid;
use Sales\Customer\Application\Finder\Customer\CustomerResult;
use Sales\Customer\Application\Query\GetCustomerByIdentityId\GetCustomerByIdentityId;
use Sales\Order\Application\Command\CancelOrder\CancelOrder;
use Sales\Order\Application\Command\PlaceOrder\PlaceOrder;
use Sales\Order\Application\Language\PublishedOrderStatus;
use Sales\Order\Application\Query\GetOrder\GetOrder;
use Sales\Order\Application\Query\ListOrders\ListOrders;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Shared\Application\Query\Result\ListResult;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Web\Controller\Criteria\OrderCriteria;
use Web\Form\FormData\OrderLineFormData;
use Web\Form\FormData\PlaceOrderFormData;
use Web\Form\PlaceOrderType;
use Web\Security\IamUser;

#[Route('/sales/orders')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
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
        $customer = $this->resolveCustomer();

        $orders = $this->queryBus->ask(new ListOrders(
            customerId: $customer->id,
            page: $criteria->page,
            itemsPerPage: $criteria->itemsPerPage,
        ));

        return $this->render('sales/order/list.html.twig', [
            'orders' => $orders,
            'cancellableStatus' => PublishedOrderStatus::PLACED->value,
            'customerId' => $customer->id,
        ]);
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route('/place', name: 'sales_order_place', methods: ['GET', 'POST'])]
    public function place(Request $request): Response
    {
        $customer = $this->resolveCustomer();

        /** @var ListResult<ProductResult> $products */
        $products = $this->queryBus->ask(new ListProducts(itemsPerPage: 100));
        $productChoices = [];
        foreach ($products->items as $product) {
            $productChoices[\sprintf('%s — %s €', $product->label, number_format($product->unitAmountInCents / 100, 2))] = $product->id;
        }

        $formData = new PlaceOrderFormData();
        $form = $this->createForm(PlaceOrderType::class, $formData, ['products' => $productChoices]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->dispatch(new PlaceOrder(
                id: Uuid::uuid7()->toString(),
                customerId: $customer->id,
                lines: array_values(array_map(
                    static fn (OrderLineFormData $line): array => [
                        'productId' => (string) $line->productId,
                        'quantity' => (int) $line->quantity,
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

        $customer = $this->resolveCustomer();
        $order = $this->queryBus->ask(new GetOrder($id));

        if ($order->customerId !== $customer->id) {
            throw $this->createAccessDeniedException('This order does not belong to you.');
        }

        $this->commandBus->dispatch(new CancelOrder($id));

        return $this->redirectToRoute('sales_order_list');
    }

    /**
     * @throws ApplicationExceptionInterface
     */
    private function resolveCustomer(): CustomerResult
    {
        $user = $this->getUser();
        \assert($user instanceof IamUser);

        $customer = $this->queryBus->ask(new GetCustomerByIdentityId($user->getUserIdentifier()));

        return $customer ?? throw $this->createAccessDeniedException('No customer is linked to this identity.');
    }
}
