<?php

declare(strict_types=1);

namespace Web\Controller;

use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Shipping\Shipment\Application\Query\ListShipments\ListShipments;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;
use Web\Controller\Criteria\ShipmentCriteria;

final readonly class ShipmentController
{
    public function __construct(
        private QueryBusInterface $queryBus,
        private Environment $twig,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     */
    #[Route('/shipments', name: 'shipments_index', methods: ['GET'])]
    public function index(#[MapQueryString] ShipmentCriteria $criteria = new ShipmentCriteria()): Response
    {
        $shipments = $this->queryBus->ask(new ListShipments(
            status: $criteria->status,
            page: $criteria->page,
            itemsPerPage: $criteria->itemsPerPage,
        ));

        return new Response($this->twig->render('shipments/index.html.twig', ['shipments' => $shipments]));
    }
}
