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
use Storefront\Controller\QueryString\RegisterQueryString;
use Storefront\Form\ConfirmationType;
use Storefront\Form\FormData\ConfirmationFormData;
use Storefront\Form\FormData\RegisterFormData;
use Storefront\Form\RegisterType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

final class RegistrationController extends AbstractController
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route(path: '/inscription', name: 'storefront_register', methods: ['GET', 'POST'])]
    public function register(Request $request, #[MapQueryString] RegisterQueryString $query): Response
    {
        if (null === $query->email || '' === $query->email) {
            return $this->redirectToRoute('security_login');
        }

        $formData = new RegisterFormData();
        $formData->email = $query->email;
        $form = $this->createForm(RegisterType::class, $formData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $id = Uuid::uuid7()->toString();

            // Registers the identity first: an email already taken then claims nothing, nothing to erase.
            try {
                $this->commandBus->dispatch(new RegisterIdentity($id, (string) $formData->fullName, $formData->email));
            } catch (IdentityEmailAlreadyInUseException) {
                $this->addFlash('error', 'Cet email est déjà utilisé.');

                return $this->render('registration/register.html.twig', ['form' => $form]);
            }

            // A rejected password here leaves an orphan PENDING identity — no manual cleanup: it
            // self-erases through the existing 24h sweep (SweepExpiredPendingIdentitiesCommand),
            // the same fate as any registration abandoned before its confirmation step.
            try {
                $this->commandBus->dispatch(new DefinePasswordCredential($id, (string) $formData->password));
            } catch (WeakPasswordException|CompromisedPasswordException) {
                $this->addFlash('error', 'Ce mot de passe est trop faible ou a été compromis.');

                return $this->render('registration/register.html.twig', ['form' => $form]);
            }

            return $this->redirectToRoute('storefront_register_confirm', ['id' => $id]);
        }

        return $this->render('registration/register.html.twig', ['form' => $form]);
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route(path: '/inscription/{id}/confirmation', name: 'storefront_register_confirm', methods: ['GET', 'POST'])]
    public function confirm(Request $request, string $id): Response
    {
        $formData = new ConfirmationFormData();
        $form = $this->createForm(ConfirmationType::class, $formData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->commandBus->dispatch(new ConfirmIdentity($id, (string) $formData->code));
            } catch (InvalidConfirmationCodeException|VerificationCodeNotFoundException|VerificationCodeAttemptsExceededException) {
                $this->addFlash('error', 'Code invalide ou expiré.');

                return $this->render('registration/confirm.html.twig', ['form' => $form, 'id' => $id]);
            }

            $this->addFlash('success', 'Compte confirmé, vous pouvez vous connecter.');

            return $this->redirectToRoute('security_login');
        }

        return $this->render('registration/confirm.html.twig', ['form' => $form, 'id' => $id]);
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route(path: '/inscription/{id}/confirmation/renvoyer', name: 'storefront_register_confirm_resend', methods: ['POST'])]
    public function confirmResend(Request $request, string $id): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('confirmation_resend', (string) $request->request->get('_csrf_token'))) {
            return $this->redirectToRoute('storefront_register_confirm', ['id' => $id]);
        }

        try {
            $this->commandBus->dispatch(new RequestConfirmation($id));
            $this->addFlash('success', 'Un nouveau code a été envoyé.');
        } catch (ConfirmationRequestedTooRecentlyException) {
            $this->addFlash('error', 'Veuillez patienter avant de redemander un code.');
        } catch (IdentityAlreadyConfirmedException) {
            return $this->redirectToRoute('security_login');
        }

        return $this->redirectToRoute('storefront_register_confirm', ['id' => $id]);
    }
}
