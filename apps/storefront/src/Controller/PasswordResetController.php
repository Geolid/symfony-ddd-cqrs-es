<?php

declare(strict_types=1);

namespace Storefront\Controller;

use Iam\Authentication\Application\Command\RequestPasswordReset\RequestPasswordReset;
use Iam\Authentication\Application\Command\ResetPassword\ResetPassword;
use Iam\Authentication\Application\CredentialVerification\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Domain\PasswordCredential\Exception\InvalidPasswordResetCodeException;
use Iam\Authentication\Domain\PasswordCredential\Exception\PasswordResetRequestedTooRecentlyException;
use Iam\Authentication\Domain\PasswordCredential\Exception\SamePasswordException;
use Iam\Authentication\Domain\PasswordCredential\Exception\WeakPasswordException;
use Iam\Identity\Application\Query\GetIdentityByEmail\GetIdentityByEmail;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Shared\Domain\Exception\VerificationCodeAttemptsExceededException;
use Shared\Domain\Exception\VerificationCodeNotFoundException;
use Storefront\Form\FormData\PasswordResetFormData;
use Storefront\Form\FormData\PasswordResetRequestFormData;
use Storefront\Form\PasswordResetRequestType;
use Storefront\Form\PasswordResetType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PasswordResetController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route(path: ['en' => '/forgot-password', 'fr' => '/mot-de-passe-oublie'], name: 'storefront_password_reset_request', methods: ['GET', 'POST'])]
    public function request(Request $request, #[MapQueryParameter] ?string $email = null): Response
    {
        $formData = new PasswordResetRequestFormData();
        $formData->email = $email;
        $form = $this->createForm(PasswordResetRequestType::class, $formData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $identity = $this->queryBus->ask(new GetIdentityByEmail((string) $formData->email));

            if (null === $identity) {
                $this->addFlash('error', $this->translator->trans('flash_not_found', domain: 'password_reset_request'));

                return $this->render('password_reset/request.html.twig', ['form' => $form]);
            }

            if (!$identity->verificationStatus->isConfirmed()) {
                return $this->redirectToRoute('storefront_register_confirm', ['id' => $identity->id]);
            }

            try {
                $this->commandBus->dispatch(new RequestPasswordReset($identity->id));
                $this->addFlash('success', $this->translator->trans('flash_sent', domain: 'password_reset_request'));
            } catch (PasswordResetRequestedTooRecentlyException) {
                $this->addFlash('success', $this->translator->trans('flash_already_sent', domain: 'password_reset_request'));
            }

            return $this->redirectToRoute('storefront_password_reset', ['identityId' => $identity->id]);
        }

        return $this->render('password_reset/request.html.twig', ['form' => $form]);
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route(path: ['en' => '/reset-password/{identityId}', 'fr' => '/reinitialiser-mot-de-passe/{identityId}'], name: 'storefront_password_reset', methods: ['GET', 'POST'])]
    public function reset(Request $request, string $identityId): Response
    {
        $formData = new PasswordResetFormData();
        $form = $this->createForm(PasswordResetType::class, $formData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->commandBus->dispatch(new ResetPassword($identityId, (string) $formData->code, (string) $formData->newPassword));
            } catch (InvalidPasswordResetCodeException|VerificationCodeNotFoundException|VerificationCodeAttemptsExceededException) {
                $this->addFlash('error', $this->translator->trans('flash_invalid_code'));

                return $this->render('password_reset/reset.html.twig', ['form' => $form, 'identityId' => $identityId]);
            } catch (WeakPasswordException|SamePasswordException) {
                $this->addFlash('error', $this->translator->trans('flash_invalid_password', domain: 'password_reset'));

                return $this->render('password_reset/reset.html.twig', ['form' => $form, 'identityId' => $identityId]);
            }

            $this->addFlash('success', $this->translator->trans('flash_reset', domain: 'password_reset'));

            return $this->redirectToRoute('storefront_signin');
        }

        return $this->render('password_reset/reset.html.twig', ['form' => $form, 'identityId' => $identityId]);
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route(path: ['en' => '/reset-password/{identityId}/resend', 'fr' => '/reinitialiser-mot-de-passe/{identityId}/renvoyer'], name: 'storefront_password_reset_resend', methods: ['POST'])]
    public function resend(Request $request, string $identityId): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('password_reset_resend', (string) $request->request->get('_token'))) {
            return $this->redirectToRoute('storefront_password_reset', ['identityId' => $identityId]);
        }

        try {
            $this->commandBus->dispatch(new RequestPasswordReset($identityId));
            $this->addFlash('success', $this->translator->trans('flash_resent'));
        } catch (PasswordResetRequestedTooRecentlyException) {
            $this->addFlash('error', $this->translator->trans('flash_too_recent'));
        } catch (IdentityNotAuthenticatableException) {
            return $this->redirectToRoute('storefront_signin');
        }

        return $this->redirectToRoute('storefront_password_reset', ['identityId' => $identityId]);
    }
}
