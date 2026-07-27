<?php

declare(strict_types=1);

namespace Web\Controller;

use Shared\Application\Query\QueryBusInterface;
use Shipping\Shipment\Application\Query\ListShipments\ListShipments;
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

    #[Route('/shipments', methods: ['GET'])]
    public function index(): Response
    {
        $shipments = $this->queryBus->ask(new ListShipments(itemsPerPage: 50));

        return new Response($this->twig->render('shipments/index.html.twig', ['shipments' => $shipments]));
    }
}
