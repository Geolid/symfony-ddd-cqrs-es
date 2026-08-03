<?php

declare(strict_types=1);

namespace Web\Controller;

use Fulfilment\Shipment\Application\Query\ListShipments\ListShipments;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Web\Controller\Criteria\ShipmentCriteria;

#[Route('/fulfilment/shipments')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ShipmentController extends AbstractController
{
    public function __construct(private readonly QueryBusInterface $queryBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     */
    #[Route(name: 'fulfilment_shipment_list', methods: ['GET'])]
    public function list(
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        ShipmentCriteria $criteria = new ShipmentCriteria(),
    ): Response {
        $shipments = $this->queryBus->ask(new ListShipments(
            status: $criteria->status,
            page: $criteria->page,
            itemsPerPage: $criteria->itemsPerPage,
        ));

        return $this->render('fulfilment/shipment/list.html.twig', ['shipments' => $shipments]);
    }
}
