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
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Web\Controller\Criteria\CustomerCriteria;
use Web\Form\FormData\RegisterCustomerFormData;
use Web\Form\RegisterCustomerType;

final readonly class CustomerController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private FormFactoryInterface $formFactory,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private TranslatorInterface $translator,
        private Environment $twig,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     */
    #[Route('/customers', name: 'customers_index', methods: ['GET'])]
    public function index(
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        CustomerCriteria $criteria = new CustomerCriteria(),
    ): Response {
        $customers = $this->queryBus->ask(new ListCustomers(
            page: $criteria->page,
            itemsPerPage: $criteria->itemsPerPage,
        ));

        return new Response($this->twig->render('customers/index.html.twig', ['customers' => $customers]));
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route('/customers/new', name: 'customers_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $formData = new RegisterCustomerFormData();
        $form = $this->formFactory->create(RegisterCustomerType::class, $formData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->commandBus->dispatch(new RegisterCustomer(
                    id: Uuid::uuid7()->toString(),
                    email: (string) $formData->email,
                ));

                return new RedirectResponse('/customers');
            } catch (AddressAlreadyRegisteredException) {
                $this->flash($request, 'error', 'customers.new.address_taken');
            }
        }

        return new Response($this->twig->render('customers/new.html.twig', ['form' => $form->createView()]));
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route('/customers/{id}/erase', methods: ['POST'])]
    public function erase(Request $request, string $id): RedirectResponse
    {
        $token = new CsrfToken('erase-customer-'.$id, (string) $request->request->get('_token'));

        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new BadRequestHttpException('Invalid CSRF token.');
        }

        $this->commandBus->dispatch(new EraseCustomer($id));
        $this->flash($request, 'success', 'customers.index.erase_confirmed');

        return new RedirectResponse('/customers');
    }

    private function flash(Request $request, string $label, string $message): void
    {
        $session = $request->getSession();

        \assert($session instanceof FlashBagAwareSessionInterface);

        $session->getFlashBag()->add($label, $this->translator->trans($message));
    }
}
