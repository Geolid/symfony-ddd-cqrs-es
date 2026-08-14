<?php

declare(strict_types=1);

namespace Web\Controller;

use Sales\Customer\Application\Command\SetCustomerBillingAddress\SetCustomerBillingAddress;
use Sales\Customer\Application\Command\SetCustomerShippingAddress\SetCustomerShippingAddress;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Web\Form\CheckoutAddressesType;
use Web\Form\FormData\CheckoutAddressesFormData;
use Web\Security\PasswordUser;

#[Route('/checkout')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class CheckoutController extends AbstractController
{
    private const array ALLOWED_RETURN_ROUTES = ['sales_order_place'];

    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route('/address', name: 'checkout_address_complete', methods: ['GET', 'POST'])]
    public function completeAddress(Request $request, #[CurrentUser] PasswordUser $user, #[MapQueryParameter] ?string $returnTo = null): Response
    {
        $returnRoute = \in_array($returnTo, self::ALLOWED_RETURN_ROUTES, true) ? $returnTo : self::ALLOWED_RETURN_ROUTES[0];

        $formData = new CheckoutAddressesFormData();
        $form = $this->createForm(CheckoutAddressesType::class, $formData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->dispatch(new SetCustomerShippingAddress(
                customerId: $user->identityId(),
                firstName: (string) $formData->shipping->fullName->firstName,
                lastName: (string) $formData->shipping->fullName->lastName,
                street: (string) $formData->shipping->address->street,
                postalCode: (string) $formData->shipping->address->postalCode,
                city: (string) $formData->shipping->address->city,
            ));
            $this->commandBus->dispatch(new SetCustomerBillingAddress(
                customerId: $user->identityId(),
                firstName: (string) $formData->billing->fullName->firstName,
                lastName: (string) $formData->billing->fullName->lastName,
                street: (string) $formData->billing->address->street,
                postalCode: (string) $formData->billing->address->postalCode,
                city: (string) $formData->billing->address->city,
            ));

            return $this->redirectToRoute($returnRoute);
        }

        return $this->render('checkout/address.html.twig', ['form' => $form]);
    }
}
