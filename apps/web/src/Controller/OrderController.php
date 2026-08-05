<?php

declare(strict_types=1);

namespace Web\Controller;

use Catalog\Product\Application\Finder\Product\ProductResult;
use Catalog\Product\Application\Query\ListProducts\ListProducts;
use Fulfilment\Shipment\Application\Query\GetShipmentByOrder\GetShipmentByOrder;
use Ramsey\Uuid\Uuid;
use Sales\Customer\Application\Finder\Customer\CustomerResult;
use Sales\Customer\Application\Query\GetCustomerByIdentityId\GetCustomerByIdentityId;
use Sales\Order\Application\Command\CancelOrder\CancelOrder;
use Sales\Order\Application\Command\PlaceOrder\PlaceOrder;
use Sales\Order\Application\Exception\OrderPaymentAlreadyRequestedException;
use Sales\Order\Application\Exception\OrderResultNotFoundException;
use Sales\Order\Application\Finder\Order\OrderResult;
use Sales\Order\Application\Payment\RequestOrderPaymentInterface;
use Sales\Order\Application\Query\GetOrder\GetOrder;
use Sales\Order\Application\Query\GetOrderLines\GetOrderLines;
use Sales\Order\Application\Query\GetOrderPaymentByOrder\GetOrderPaymentByOrder;
use Sales\Order\Application\Query\ListOrders\ListOrders;
use Sales\OrderTracking\Application\Query\GetOrderTracking\GetOrderTracking;
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
use Symfony\Contracts\Translation\TranslatorInterface;
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
        private readonly RequestOrderPaymentInterface $orderPaymentRequester,
        private readonly TranslatorInterface $translator,
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

        $trackings = [];
        foreach ($orders->items as $order) {
            $trackings[$order->id] = $this->queryBus->ask(new GetOrderTracking($order->id));
        }

        return $this->render('sales/order/list.html.twig', [
            'orders' => $orders,
            'trackings' => $trackings,
        ]);
    }

    /**
     * @throws ApplicationExceptionInterface
     */
    #[Route('/{id}', name: 'sales_order_show', methods: ['GET'], requirements: ['id' => Requirement::UUID])]
    public function show(string $id): Response
    {
        $customer = $this->resolveCustomer();

        try {
            $order = $this->resolveOwnedOrder($id, $customer);
        } catch (OrderResultNotFoundException) {
            throw $this->createNotFoundException('No order carries that identifier.');
        }

        $tracking = $this->queryBus->ask(new GetOrderTracking($id));
        \assert(null !== $tracking);

        return $this->render('sales/order/show.html.twig', [
            'order' => $order,
            'lines' => $this->queryBus->ask(new GetOrderLines($id)),
            'tracking' => $tracking,
            'payment' => $this->queryBus->ask(new GetOrderPaymentByOrder($id)),
            'shipment' => $this->queryBus->ask(new GetShipmentByOrder($id)),
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
        $productPricesInCents = [];
        foreach ($products->items as $product) {
            $productChoices[\sprintf('%s — %s €', $product->label, number_format($product->unitAmountInCents / 100, 2))] = $product->id;
            $productPricesInCents[$product->id] = $product->unitAmountInCents;
        }

        $formData = new PlaceOrderFormData();
        $form = $this->createForm(PlaceOrderType::class, $formData, [
            'products' => $productChoices,
            'productPricesInCents' => $productPricesInCents,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $id = Uuid::uuid7()->toString();

            $this->commandBus->dispatch(new PlaceOrder(
                id: $id,
                customerId: $customer->id,
                lines: array_values(array_map(
                    static fn (OrderLineFormData $line): array => [
                        'productId' => (string) $line->productId,
                        'quantity' => (int) $line->quantity,
                    ],
                    $formData->lines,
                )),
            ));

            $this->addFlash('success', $this->translator->trans('sales.order.flash.placed'));

            return $this->redirectToRoute('sales_order_show', ['id' => $id]);
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
        $this->resolveOwnedOrder($id, $customer);

        try {
            $this->commandBus->dispatch(new CancelOrder($id));
        } catch (OrderPaymentAlreadyRequestedException) {
            $this->addFlash('error', $this->translator->trans('sales.order.flash.cannot_cancel_paid'));

            return $this->redirectToRoute('sales_order_show', ['id' => $id]);
        }

        $this->addFlash('success', $this->translator->trans('sales.order.flash.cancelled'));

        return $this->redirectToRoute('sales_order_show', ['id' => $id]);
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route('/{id}/pay', name: 'sales_order_pay', methods: ['POST'], requirements: ['id' => Requirement::UUID])]
    public function pay(Request $request, string $id): Response
    {
        if (!$this->isCsrfTokenValid('pay-order-'.$id, (string) $request->request->get('_token'))) {
            throw new BadRequestHttpException('Invalid CSRF token.');
        }

        $customer = $this->resolveCustomer();
        $this->resolveOwnedOrder($id, $customer);

        try {
            $this->orderPaymentRequester->requestFor($id);
        } catch (OrderPaymentAlreadyRequestedException) {
            $this->addFlash('error', $this->translator->trans('sales.order.flash.payment_already_requested'));

            return $this->redirectToRoute('sales_order_show', ['id' => $id]);
        } catch (OrderResultNotFoundException) {
            throw $this->createNotFoundException('No order carries that identifier.');
        }

        $this->addFlash('success', $this->translator->trans('sales.order.flash.payment_requested'));

        return $this->redirectToRoute('sales_order_show', ['id' => $id]);
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

    /**
     * @throws ApplicationExceptionInterface
     */
    private function resolveOwnedOrder(string $id, CustomerResult $customer): OrderResult
    {
        $order = $this->queryBus->ask(new GetOrder($id));

        if ($order->customerId !== $customer->id) {
            throw $this->createAccessDeniedException('This order does not belong to you.');
        }

        return $order;
    }
}
