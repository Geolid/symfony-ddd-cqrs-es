<?php

declare(strict_types=1);

namespace Web\Controller;

use Shared\Application\Query\QueryBusInterface;
use Shipping\Shipment\Application\Query\ListShipments\ListShipments;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

final readonly class ShipmentController
{
    public function __construct(
        private QueryBusInterface $queryBus,
        private Environment $twig,
    ) {
    }

    #[Route('/shipments', name: 'shipments_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $shipments = $this->queryBus->ask(new ListShipments(
            page: $request->query->getInt('page', 1),
            itemsPerPage: 10,
        ));

        return new Response($this->twig->render('shipments/index.html.twig', ['shipments' => $shipments]));
    }
}
