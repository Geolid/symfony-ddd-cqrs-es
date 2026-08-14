<?php

declare(strict_types=1);

namespace Web\Controller;

use Catalog\Product\Application\Finder\Product\ProductResult;
use Catalog\Product\Application\Query\ListProducts\ListProducts;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Command\CancelOrder\CancelOrder;
use Sales\Order\Application\Command\PlaceOrder\PlaceOrder;
use Sales\Order\Application\Exception\OrderPaymentAlreadyCapturedException;
use Sales\Order\Application\Exception\OutdatedOrderException;
use Sales\Order\Application\Payment\OrderPaymentRequesterInterface;
use Sales\OrderSummary\Application\Query\GetOrderSummary\GetOrderSummary;
use Sales\OrderSummary\Application\Query\GetOrderSummaryLines\GetOrderSummaryLines;
use Sales\OrderSummary\Application\Query\ListOrderSummaries\ListOrderSummaries;
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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use Web\Controller\Criteria\OrderCriteria;
use Web\Exception\MissingCatalogSnapshotException;
use Web\Form\FormData\OrderLineFormData;
use Web\Form\FormData\PlaceOrderFormData;
use Web\Form\PlaceOrderType;
use Web\Security\PasswordUser;
use Web\Security\Voter\OrderVoter;
use Web\Session\CatalogSnapshot;

#[Route('/sales/orders')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class OrderController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
        private readonly OrderPaymentRequesterInterface $orderPaymentRequester,
        private readonly TranslatorInterface $translator,
        private readonly CatalogSnapshot $catalogSnapshot,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     */
    #[Route(name: 'sales_order_list', methods: ['GET'])]
    public function list(
        #[CurrentUser]
        PasswordUser $user,
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        OrderCriteria $criteria = new OrderCriteria(),
    ): Response {
        $orders = $this->queryBus->ask(new ListOrderSummaries(
            customerId: $user->identityId(),
            page: $criteria->page,
            itemsPerPage: $criteria->itemsPerPage,
            sortedByPlacedAt: true,
        ));

        return $this->render('sales/order/list.html.twig', [
            'orders' => $orders,
        ]);
    }

    /**
     * @throws ApplicationExceptionInterface
     */
    #[Route('/{id}', name: 'sales_order_show', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    public function show(string $id): Response
    {
        $summary = $this->queryBus->ask(new GetOrderSummary($id));

        $this->denyAccessUnlessGranted(OrderVoter::VIEW, $summary);

        return $this->render('sales/order/show.html.twig', [
            'order' => $summary,
            'lines' => $this->queryBus->ask(new GetOrderSummaryLines($id)),
        ]);
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route('/place', name: 'sales_order_place', methods: ['GET', 'POST'])]
    public function place(Request $request, #[CurrentUser] PasswordUser $user): Response
    {
        /** @var ListResult<ProductResult> $products */
        $products = $this->queryBus->ask(new ListProducts(itemsPerPage: 100));
        $productChoices = [];
        $productPricesInCents = [];
        $currentCatalog = [];
        foreach ($products->items as $product) {
            $productChoices[\sprintf('%s — %s €', $product->label, number_format($product->unitAmountInCents / 100, 2))] = $product->id;
            $productPricesInCents[$product->id] = $product->unitAmountInCents;
            $currentCatalog[$product->id] = ['label' => $product->label, 'unitAmountInCents' => $product->unitAmountInCents];
        }

        $formData = new PlaceOrderFormData();
        $form = $this->createForm(PlaceOrderType::class, $formData, [
            'products' => $productChoices,
            'productPricesInCents' => $productPricesInCents,
        ]);
        $form->handleRequest($request);

        if ($request->isMethod('GET')) {
            $this->catalogSnapshot->store($currentCatalog);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $lines = array_map(
                static fn (OrderLineFormData $line): array => [
                    'productId' => (string) $line->productId,
                    'quantity' => (int) $line->quantity,
                ],
                $formData->lines,
            );

            $id = Uuid::uuid7()->toString();

            try {
                $this->commandBus->dispatch(new PlaceOrder(
                    id: $id,
                    customerId: $user->identityId(),
                    lines: $this->catalogSnapshot->resolveLines($lines),
                ));
            } catch (OutdatedOrderException|MissingCatalogSnapshotException) {
                $this->addFlash('error', $this->translator->trans('sales.order.flash.catalog_changed'));

                return $this->redirectToRoute('sales_order_place');
            }

            return $this->redirectToRoute('sales_order_show', ['id' => $id]);
        }

        return $this->render('sales/order/place.html.twig', ['form' => $form]);
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route('/{id}/checkout', name: 'sales_order_pay', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    public function pay(string $id): Response
    {
        $summary = $this->queryBus->ask(new GetOrderSummary($id));

        $this->denyAccessUnlessGranted(OrderVoter::VIEW, $summary);

        if (null !== $summary->paymentCheckoutUrl) {
            return $this->redirect($summary->paymentCheckoutUrl);
        }

        $returnUrl = $this->generateUrl('sales_order_show', ['id' => $id], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->redirect($this->orderPaymentRequester->requestFor($id, $returnUrl));
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route('/{id}/cancel', name: 'sales_order_cancel', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    public function cancel(Request $request, string $id, #[CurrentUser] PasswordUser $user): Response
    {
        if (!$this->isCsrfTokenValid('cancel-order-'.$id, (string) $request->request->get('_token'))) {
            throw new BadRequestHttpException('Invalid CSRF token.');
        }

        try {
            $this->commandBus->dispatch(new CancelOrder($id, $user->identityId()));
        } catch (OrderPaymentAlreadyCapturedException) {
            $this->addFlash('error', $this->translator->trans('sales.order.flash.cannot_cancel_paid'));

            return $this->redirectToRoute('sales_order_show', ['id' => $id]);
        }

        $this->addFlash('success', $this->translator->trans('sales.order.flash.cancelled'));

        return $this->redirectToRoute('sales_order_show', ['id' => $id]);
    }
}
