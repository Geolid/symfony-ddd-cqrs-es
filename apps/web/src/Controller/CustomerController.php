<?php

declare(strict_types=1);

namespace Web\Controller;

use Ramsey\Uuid\Uuid;
use Sales\Customer\Application\Command\EraseCustomer\EraseCustomer;
use Sales\Customer\Application\Command\RegisterCustomer\RegisterCustomer;
use Sales\Customer\Application\Exception\AddressAlreadyRegisteredException;
use Sales\Customer\Application\Query\ListCustomers\ListCustomers;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Web\Controller\Criteria\CustomerCriteria;
use Web\Form\FormData\RegisterCustomerFormData;
use Web\Form\RegisterCustomerType;

#[Route('/sales/customers')]
final class CustomerController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     */
    #[Route(name: 'sales_customer_list', methods: ['GET'])]
    public function list(
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        CustomerCriteria $criteria = new CustomerCriteria(),
    ): Response {
        $customers = $this->queryBus->ask(new ListCustomers(
            page: $criteria->page,
            itemsPerPage: $criteria->itemsPerPage,
        ));

        return $this->render('sales/customer/list.html.twig', ['customers' => $customers]);
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route('/register', name: 'sales_customer_register', methods: ['GET', 'POST'])]
    public function register(Request $request): Response
    {
        $formData = new RegisterCustomerFormData();
        $form = $this->createForm(RegisterCustomerType::class, $formData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->commandBus->dispatch(new RegisterCustomer(
                    id: Uuid::uuid7()->toString(),
                    email: (string) $formData->email,
                ));

                return $this->redirectToRoute('sales_customer_list');
            } catch (AddressAlreadyRegisteredException) {
                $this->addFlash('error', $this->translator->trans('sales.customer.flash.address_taken'));
            }
        }

        return $this->render('sales/customer/register.html.twig', ['form' => $form]);
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route('/{id}/erase', name: 'sales_customer_erase', methods: ['POST'])]
    public function erase(Request $request, string $id): Response
    {
        if (!$this->isCsrfTokenValid('erase-customer-'.$id, (string) $request->request->get('_token'))) {
            throw new BadRequestHttpException('Invalid CSRF token.');
        }

        $this->commandBus->dispatch(new EraseCustomer($id));
        $this->addFlash('success', $this->translator->trans('sales.customer.flash.erased'));

        return $this->redirectToRoute('sales_customer_list');
    }
}
