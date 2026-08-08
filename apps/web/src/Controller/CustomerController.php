<?php

declare(strict_types=1);

namespace Web\Controller;

use Iam\Identity\Application\Command\RegisterIdentity\RegisterIdentity;
use Iam\Identity\Application\Command\ReleasePasswordCredentialLogin\ReleasePasswordCredentialLogin;
use Iam\Identity\Application\Command\SetPasswordCredential\SetPasswordCredential;
use Iam\Identity\Application\Exception\LoginAlreadyTakenException;
use Ramsey\Uuid\Uuid;
use Sales\Customer\Application\Command\EraseCustomer\EraseCustomer;
use Sales\Customer\Application\Command\LinkCustomerIdentity\LinkCustomerIdentity;
use Sales\Customer\Application\Command\RegisterCustomer\RegisterCustomer;
use Sales\Customer\Application\Exception\AddressAlreadyRegisteredException;
use Sales\Customer\Application\Finder\Customer\CustomerResult;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Web\Form\ChangePasswordType;
use Web\Form\FormData\ChangePasswordFormData;
use Web\Form\FormData\RegisterCustomerFormData;
use Web\Form\RegisterCustomerType;
use Web\Security\Attribute\CurrentCustomer;

#[Route('/sales/customers')]
final class CustomerController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly TranslatorInterface $translator,
        #[Autowire(service: 'security.logout_url_generator')]
        private readonly LogoutUrlGenerator $logoutUrlGenerator,
    ) {
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
            $login = (string) $formData->login;
            $email = (string) $formData->email;

            try {
                $customerId = Uuid::uuid7()->toString();
                $this->commandBus->dispatch(new RegisterCustomer(id: $customerId, email: $email));
            } catch (AddressAlreadyRegisteredException) {
                $this->addFlash('error', $this->translator->trans('sales.customer.flash.address_taken'));

                return $this->render('sales/customer/register.html.twig', ['form' => $form]);
            }

            try {
                $identityId = Uuid::uuid7()->toString();
                $this->commandBus->dispatch(new RegisterIdentity($identityId));
                $this->commandBus->dispatch(new SetPasswordCredential($identityId, $login, (string) $formData->password));
            } catch (LoginAlreadyTakenException) {
                $this->addFlash('error', $this->translator->trans('sales.customer.flash.login_taken'));

                return $this->render('sales/customer/register.html.twig', ['form' => $form]);
            }

            $this->commandBus->dispatch(new LinkCustomerIdentity($customerId, $identityId));
            $this->addFlash('success', $this->translator->trans('sales.customer.flash.registered'));

            return $this->redirectToRoute('security_login');
        }

        return $this->render('sales/customer/register.html.twig', ['form' => $form]);
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route('/erase', name: 'sales_customer_erase', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function erase(Request $request, #[CurrentCustomer] CustomerResult $customer): Response
    {
        if (!$this->isCsrfTokenValid('erase-customer', (string) $request->request->get('_token'))) {
            throw new BadRequestHttpException('Invalid CSRF token.');
        }

        $this->commandBus->dispatch(new EraseCustomer($customer->id));

        if (null !== $customer->identityId) {
            $this->commandBus->dispatch(new ReleasePasswordCredentialLogin($customer->identityId));
        }

        $this->addFlash('success', $this->translator->trans('sales.customer.flash.erased'));

        return new RedirectResponse($this->logoutUrlGenerator->getLogoutPath());
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route('/profile', name: 'sales_customer_profile', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function profile(Request $request, #[CurrentCustomer] CustomerResult $customer): Response
    {
        $formData = new ChangePasswordFormData();
        $form = $this->createForm(ChangePasswordType::class, $formData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->dispatch(new SetPasswordCredential(
                (string) $customer->identityId,
                (string) $customer->email,
                (string) $formData->password,
            ));

            $this->addFlash('success', $this->translator->trans('sales.customer.flash.password_changed'));

            return $this->redirectToRoute('sales_order_list');
        }

        return $this->render('sales/customer/profile.html.twig', ['form' => $form]);
    }
}
