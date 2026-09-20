<?php

declare(strict_types=1);

namespace Storefront\Controller;

use Iam\Authentication\Application\BreachDatabase\Exception\CompromisedPasswordException;
use Iam\Authentication\Application\Command\DefinePasswordCredential\DefinePasswordCredential;
use Iam\Authentication\Domain\PasswordCredential\Exception\WeakPasswordException;
use Iam\Identity\Application\Command\ConfirmIdentity\ConfirmIdentity;
use Iam\Identity\Application\Command\RegisterIdentity\Exception\IdentityEmailAlreadyInUseException;
use Iam\Identity\Application\Command\RegisterIdentity\RegisterIdentity;
use Iam\Identity\Application\Command\RequestConfirmation\RequestConfirmation;
use Iam\Identity\Domain\Exception\ConfirmationRequestedTooRecentlyException;
use Iam\Identity\Domain\Exception\IdentityAlreadyConfirmedException;
use Iam\Identity\Domain\Exception\InvalidConfirmationCodeException;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Domain\Exception\VerificationCodeAttemptsExceededException;
use Shared\Domain\Exception\VerificationCodeNotFoundException;
use Storefront\Form\ConfirmationType;
use Storefront\Form\FormData\ConfirmationFormData;
use Storefront\Form\FormData\RegisterFormData;
use Storefront\Form\RegisterType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

final class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route(path: ['en' => '/register', 'fr' => '/inscription'], name: 'storefront_register', methods: ['GET', 'POST'])]
    public function register(Request $request, #[MapQueryParameter] ?string $email = null): Response
    {
        if (null === $email || '' === $email) {
            return $this->redirectToRoute('storefront_signin');
        }

        $formData = new RegisterFormData();
        $formData->email = $email;
        $form = $this->createForm(RegisterType::class, $formData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $id = Uuid::uuid7()->toString();

            // Registers the identity first: an email already taken then claims nothing, nothing to erase.
            try {
                $this->commandBus->dispatch(new RegisterIdentity($id, (string) $formData->fullName, $formData->email));
            } catch (IdentityEmailAlreadyInUseException) {
                $this->addFlash('error', $this->translator->trans('flash_email_taken', domain: 'register'));

                return $this->render('registration/register.html.twig', ['form' => $form]);
            }

            // A rejected password here leaves an orphan PENDING identity — no manual cleanup: it
            // self-erases through the existing 24h sweep (SweepExpiredPendingIdentitiesCommand),
            // the same fate as any registration abandoned before its confirmation step.
            try {
                $this->commandBus->dispatch(new DefinePasswordCredential($id, (string) $formData->password));
            } catch (WeakPasswordException|CompromisedPasswordException) {
                $this->addFlash('error', $this->translator->trans('flash_weak_password', domain: 'register'));

                return $this->render('registration/register.html.twig', ['form' => $form]);
            }

            return $this->redirectToRoute('storefront_register_confirm', ['id' => $id, 'email' => $formData->email]);
        }

        return $this->render('registration/register.html.twig', ['form' => $form]);
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route(path: ['en' => '/register/{id}/confirm', 'fr' => '/inscription/{id}/confirmation'], name: 'storefront_register_confirm', methods: ['GET', 'POST'])]
    public function confirm(Request $request, string $id, #[MapQueryParameter] ?string $email = null): Response
    {
        $formData = new ConfirmationFormData();
        $form = $this->createForm(ConfirmationType::class, $formData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->commandBus->dispatch(new ConfirmIdentity($id, (string) $formData->code));
            } catch (InvalidConfirmationCodeException|VerificationCodeNotFoundException|VerificationCodeAttemptsExceededException) {
                $this->addFlash('error', $this->translator->trans('flash_invalid_code'));

                return $this->render('registration/confirm.html.twig', ['form' => $form, 'id' => $id, 'email' => $email]);
            }

            $this->addFlash('success', $this->translator->trans('flash_confirmed', domain: 'confirm'));

            return $this->redirectToRoute('storefront_signin');
        }

        return $this->render('registration/confirm.html.twig', ['form' => $form, 'id' => $id, 'email' => $email]);
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route(path: ['en' => '/register/{id}/confirm/resend', 'fr' => '/inscription/{id}/confirmation/renvoyer'], name: 'storefront_register_confirm_resend', methods: ['POST'])]
    public function confirmResend(Request $request, string $id, #[MapQueryParameter] ?string $email = null): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('confirmation_resend', (string) $request->request->get('_token'))) {
            return $this->redirectToRoute('storefront_register_confirm', ['id' => $id, 'email' => $email]);
        }

        try {
            $this->commandBus->dispatch(new RequestConfirmation($id));
            $this->addFlash('success', $this->translator->trans('flash_resent'));
        } catch (ConfirmationRequestedTooRecentlyException) {
            $this->addFlash('error', $this->translator->trans('flash_too_recent'));
        } catch (IdentityAlreadyConfirmedException) {
            return $this->redirectToRoute('storefront_signin');
        }

        return $this->redirectToRoute('storefront_register_confirm', ['id' => $id, 'email' => $email]);
    }
}
