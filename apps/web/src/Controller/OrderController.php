<?php

declare(strict_types=1);

namespace Web\Controller;

use AfterSales\Withdrawal\Application\Command\RequestWithdrawal\RequestWithdrawal;
use AfterSales\Withdrawal\Application\Exception\OrderResultNotFoundException;
use AfterSales\Withdrawal\Domain\Exception\WithdrawalWindowExpiredException;
use Catalog\Product\Application\Finder\Product\ProductResult;
use Catalog\Product\Application\Query\ListProducts\ListProducts;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Command\CancelOrder\CancelOrder;
use Sales\Order\Application\Command\PlaceOrder\PlaceOrder;
use Sales\Order\Application\Exception\BuyerAddressesNotCompletedException;
use Sales\Order\Application\Exception\OrderPaymentRequestInProgressException;
use Sales\Order\Application\Exception\OutdatedOrderException;
use Sales\Order\Application\Payment\OrderPaymentRequesterInterface;
use Sales\Order\Domain\Exception\OrderAlreadyCancelledException;
use Sales\Order\Domain\Exception\OrderNotCancellableException;
use Sales\OrderSummary\Application\Query\GetOrderSummary\GetOrderSummary;
use Sales\OrderSummary\Application\Query\ListOrderSummaries\ListOrderSummaries;
use Sales\OrderSummary\Application\Query\ListOrderSummaryLines\ListOrderSummaryLines;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Shared\Application\Query\Result\PaginatedResult;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
use Web\Controller\QueryString\ListQueryString;
use Web\Exception\MissingCatalogSnapshotException;
use Web\Form\FormData\OrderLineFormData;
use Web\Form\FormData\PlaceOrderFormData;
use Web\Form\PlaceOrderType;
use Web\Security\PasswordUser;
use Web\Security\Voter\OrderVoter;
use Web\Session\CatalogSnapshot;

#[Route(path: ['en' => '/sales/orders', 'fr' => '/ventes/commandes'])]
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
        ListQueryString $queryString = new ListQueryString(),
    ): Response {
        $orders = $this->queryBus->ask(new ListOrderSummaries(
            customerId: $user->identityId(),
            page: $queryString->page,
            itemsPerPage: $queryString->itemsPerPage,
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
            'lines' => $this->queryBus->ask(new ListOrderSummaryLines($id)),
        ]);
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route(path: ['en' => '/place', 'fr' => '/commander'], name: 'sales_order_place', methods: ['GET', 'POST'])]
    public function place(Request $request, #[CurrentUser] PasswordUser $user): Response
    {
        /** @var PaginatedResult<ProductResult> $products */
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
            } catch (BuyerAddressesNotCompletedException) {
                return $this->redirectToRoute('checkout_address_complete', ['return_to' => 'sales_order_place']);
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
    #[Route(path: ['en' => '/{id}/checkout', 'fr' => '/{id}/paiement'], name: 'sales_order_pay', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    public function pay(string $id): Response
    {
        $summary = $this->queryBus->ask(new GetOrderSummary($id));

        $this->denyAccessUnlessGranted(OrderVoter::VIEW, $summary);

        $returnUrl = $this->generateUrl('sales_order_show', ['id' => $id], UrlGeneratorInterface::ABSOLUTE_URL);

        try {
            return $this->redirect($this->orderPaymentRequester->requestFor($id, $returnUrl));
        } catch (OrderAlreadyCancelledException) {
            $this->addFlash('error', $this->translator->trans('sales.order.flash.cannot_pay_cancelled'));

            return $this->redirectToRoute('sales_order_show', ['id' => $id]);
        } catch (OrderPaymentRequestInProgressException) {
            return $this->render('sales/order/payment_in_progress.html.twig', [], new Response(null, Response::HTTP_CONFLICT, ['Refresh' => '3']));
        }
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route(path: ['en' => '/{id}/cancel', 'fr' => '/{id}/annuler'], name: 'sales_order_cancel', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    public function cancel(Request $request, string $id, #[CurrentUser] PasswordUser $user): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('cancel-order-'.$id, (string) $request->request->get('_token'))) {
            throw new BadRequestHttpException('Invalid CSRF token.');
        }

        try {
            $this->commandBus->dispatch(new CancelOrder($id, $user->identityId()));
        } catch (OrderNotCancellableException) {
            $this->addFlash('error', $this->translator->trans('sales.order.flash.not_cancellable'));

            return $this->redirectToRoute('sales_order_show', ['id' => $id]);
        }

        $this->addFlash('success', $this->translator->trans('sales.order.flash.cancelled'));

        return $this->redirectToRoute('sales_order_show', ['id' => $id]);
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route(path: ['en' => '/{id}/request-return', 'fr' => '/{id}/demander-un-retour'], name: 'sales_order_request_return', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    public function requestReturn(Request $request, string $id, #[CurrentUser] PasswordUser $user): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('request-order-return-'.$id, (string) $request->request->get('_token'))) {
            throw new BadRequestHttpException('Invalid CSRF token.');
        }

        try {
            $this->commandBus->dispatch(new RequestWithdrawal($id, $user->identityId()));
        } catch (OrderResultNotFoundException) {
            $this->addFlash('error', $this->translator->trans('sales.order.flash.not_returnable'));

            return $this->redirectToRoute('sales_order_show', ['id' => $id]);
        } catch (WithdrawalWindowExpiredException) {
            $this->addFlash('error', $this->translator->trans('sales.order.flash.return_window_expired'));

            return $this->redirectToRoute('sales_order_show', ['id' => $id]);
        }

        $this->addFlash('success', $this->translator->trans('sales.order.flash.return_requested'));

        return $this->redirectToRoute('sales_order_show', ['id' => $id]);
    }
}
