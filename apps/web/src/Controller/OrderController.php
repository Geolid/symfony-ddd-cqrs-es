<?php

declare(strict_types=1);

namespace Web\Controller;

use Ordering\Order\Application\Command\CancelOrder\CancelOrder;
use Ordering\Order\Application\Command\PlaceOrder\PlaceOrder;
use Ordering\Order\Application\Query\ListOrders\ListOrders;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Query\QueryBusInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

final readonly class OrderController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private Environment $twig,
    ) {
    }

    #[Route('/', methods: ['GET'])]
    #[Route('/orders', methods: ['GET'])]
    public function index(): Response
    {
        $orders = $this->queryBus->ask(new ListOrders(itemsPerPage: 50));

        return new Response($this->twig->render('orders/index.html.twig', ['orders' => $orders]));
    }

    #[Route('/orders/new', methods: ['GET'])]
    public function new(): Response
    {
        return new Response($this->twig->render('orders/new.html.twig'));
    }

    #[Route('/orders', methods: ['POST'])]
    public function create(Request $request): RedirectResponse
    {
        $this->commandBus->dispatch(new PlaceOrder(
            id: Uuid::uuid7()->toString(),
            customerId: (string) $request->request->get('customerId'),
            totalAmountInCents: (int) $request->request->get('totalAmountInCents'),
        ));

        return new RedirectResponse('/orders');
    }

    #[Route('/orders/{id}/cancel', methods: ['POST'])]
    public function cancel(string $id): RedirectResponse
    {
        $this->commandBus->dispatch(new CancelOrder($id));

        return new RedirectResponse('/orders');
    }
}
