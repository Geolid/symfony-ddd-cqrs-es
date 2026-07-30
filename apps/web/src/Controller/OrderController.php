<?php

declare(strict_types=1);

namespace Web\Controller;

use Ordering\Order\Application\Command\CancelOrder\CancelOrder;
use Ordering\Order\Application\Command\PlaceOrder\PlaceOrder;
use Ordering\Order\Application\Query\ListOrders\ListOrders;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;
use Web\Controller\Criteria\OrderCriteria;
use Web\Form\FormData\PlaceOrderFormData;
use Web\Form\PlaceOrderType;

final readonly class OrderController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private FormFactoryInterface $formFactory,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private Environment $twig,
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
    ): Response
    {
        $orders = $this->queryBus->ask(new ListOrders(
            page: $criteria->page,
            itemsPerPage: $criteria->itemsPerPage,
        ));

        return new Response($this->twig->render('orders/index.html.twig', ['orders' => $orders]));
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route('/orders/new', name: 'orders_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $formData = new PlaceOrderFormData();
        $form = $this->formFactory->create(PlaceOrderType::class, $formData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->dispatch(new PlaceOrder(
                id: Uuid::uuid7()->toString(),
                customerId: (string) $formData->customerId,
                totalAmountInCents: (int) $formData->totalAmountInCents,
            ));

            return new RedirectResponse('/orders');
        }

        return new Response($this->twig->render('orders/new.html.twig', ['form' => $form->createView()]));
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route('/orders/{id}/cancel', methods: ['POST'])]
    public function cancel(Request $request, string $id): RedirectResponse
    {
        $token = new CsrfToken('cancel-order-'.$id, (string) $request->request->get('_token'));

        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new BadRequestHttpException('Invalid CSRF token.');
        }

        $this->commandBus->dispatch(new CancelOrder($id));

        return new RedirectResponse('/orders');
    }
}
