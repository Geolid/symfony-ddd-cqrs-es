<?php

declare(strict_types=1);

namespace Web\Controller;

use Sales\Customer\Application\Command\RegisterCustomerBillingAddress\RegisterCustomerBillingAddress;
use Sales\Customer\Application\Command\RegisterCustomerShippingAddress\RegisterCustomerShippingAddress;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Web\Controller\QueryString\CompleteAddressQueryString;
use Web\Form\CheckoutAddressesType;
use Web\Form\FormData\CheckoutAddressesFormData;
use Web\Security\PasswordUser;

#[Route(path: ['en' => '/checkout', 'fr' => '/finalisation'])]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class CheckoutController extends AbstractController
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route(path: ['en' => '/address', 'fr' => '/adresse'], name: 'checkout_address_complete', methods: ['GET', 'POST'])]
    public function completeAddress(
        Request $request,
        #[CurrentUser]
        PasswordUser $user,
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        CompleteAddressQueryString $queryString = new CompleteAddressQueryString(),
    ): Response {
        $formData = new CheckoutAddressesFormData();
        $form = $this->createForm(CheckoutAddressesType::class, $formData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->dispatch(new RegisterCustomerShippingAddress(
                customerId: $user->identityId(),
                firstName: (string) $formData->shipping->fullName->firstName,
                lastName: (string) $formData->shipping->fullName->lastName,
                street: (string) $formData->shipping->address->street,
                postalCode: (string) $formData->shipping->address->postalCode,
                city: (string) $formData->shipping->address->city,
                countryCode: (string) $formData->shipping->address->countryCode,
            ));
            $this->commandBus->dispatch(new RegisterCustomerBillingAddress(
                customerId: $user->identityId(),
                firstName: (string) $formData->billing->fullName->firstName,
                lastName: (string) $formData->billing->fullName->lastName,
                street: (string) $formData->billing->address->street,
                postalCode: (string) $formData->billing->address->postalCode,
                city: (string) $formData->billing->address->city,
                countryCode: (string) $formData->billing->address->countryCode,
            ));

            return $this->redirectToRoute($queryString->returnTo);
        }

        return $this->render('checkout/address.html.twig', ['form' => $form]);
    }
}
