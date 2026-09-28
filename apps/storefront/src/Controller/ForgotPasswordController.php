<?php

declare(strict_types=1);

namespace Storefront\Controller;

use Iam\Authentication\Application\Command\RequestPasswordReset\RequestPasswordReset;
use Iam\Authentication\Application\Command\ResetPassword\ResetPassword;
use Iam\Authentication\Application\CredentialVerification\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Domain\PasswordCredential\Exception\PasswordResetRequestedTooRecentlyException;
use Iam\Identity\Application\Query\GetIdentityByEmail\GetIdentityByEmail;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Storefront\Form\FormExceptionMapper;
use Storefront\Form\PasswordReset\PasswordResetFormData;
use Storefront\Form\PasswordReset\PasswordResetType;
use Storefront\Form\PasswordResetRequest\PasswordResetRequestFormData;
use Storefront\Form\PasswordResetRequest\PasswordResetRequestType;
use Storefront\Security\RateLimiter\VerificationCodeRateLimiter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: ['en' => '/forgot-password', 'fr' => '/mot-de-passe-oublie'], name: 'storefront_forgot_password_')]
final class ForgotPasswordController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
        private readonly TranslatorInterface $translator,
        private readonly FormExceptionMapper $formExceptionMapper,
        private readonly ClockInterface $clock,
        private readonly VerificationCodeRateLimiter $rateLimiter,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route(name: 'request', methods: ['GET', 'POST'])]
    public function request(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        $formData = new PasswordResetRequestFormData();
        $formData->email = $authenticationUtils->getLastUsername() ?: null;
        $form = $this->createForm(PasswordResetRequestType::class, $formData)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $identity = $this->queryBus->ask(new GetIdentityByEmail((string) $formData->email));

            if (null === $identity) {
                $form->get('email')->addError(new FormError($this->translator->trans('request_error_not_found', domain: 'forgot_password')));

                return $this->render('forgot_password/request.html.twig', ['form' => $form]);
            }

            $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, (string) $formData->email);

            if (!$identity->verificationStatus->isConfirmed()) {
                return $this->redirectToRoute('storefront_registration_confirm', ['identityId' => $identity->id]);
            }

            $this->requestPasswordResetCode($request, $identity->id);

            return $this->redirectToRoute('storefront_forgot_password_reset', ['identityId' => $identity->id]);
        }

        return $this->render('forgot_password/request.html.twig', ['form' => $form]);
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route(path: ['en' => '/{identityId}/reset', 'fr' => '/{identityId}/reinitialiser'], name: 'reset', requirements: ['identityId' => Requirement::UUID_V7], methods: ['GET', 'POST'])]
    public function reset(Request $request, string $identityId, AuthenticationUtils $authenticationUtils): Response
    {
        $formData = new PasswordResetFormData();
        $form = $this->createForm(PasswordResetType::class, $formData)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->commandBus->dispatch(new ResetPassword($identityId, (string) $formData->code, (string) $formData->newPassword));
                $this->rateLimiter->reset($request, $identityId, 'reset');
                $this->addFlash('success', $this->translator->trans('reset_flash_reset', domain: 'forgot_password'));

                return $this->redirectToRoute('storefront_signin_identify');
            } catch (IdentityNotAuthenticatableException) {
                $this->addFlash('error', $this->translator->trans('reset_flash_not_authenticatable', domain: 'forgot_password'));

                return $this->redirectToRoute('storefront_signin_identify');
            } catch (ApplicationExceptionInterface|\DomainException $e) {
                if (!$this->formExceptionMapper->map($form, $e)) {
                    throw $e;
                }
            }
        }

        return $this->render('forgot_password/reset.html.twig', [
            'form' => $form,
            'identityId' => $identityId,
            'email' => $authenticationUtils->getLastUsername(),
        ]);
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route(path: ['en' => '/{identityId}/reset/resend', 'fr' => '/{identityId}/reinitialiser/renvoyer'], name: 'reset_resend', requirements: ['identityId' => Requirement::UUID_V7], methods: ['POST'])]
    public function resetResend(Request $request, string $identityId): RedirectResponse
    {
        if ($this->isCsrfTokenValid('password_reset_resend', (string) $request->request->get('_token'))) {
            try {
                $this->requestPasswordResetCode($request, $identityId);
            } catch (IdentityNotAuthenticatableException) {
                $this->addFlash('error', $this->translator->trans('reset_flash_not_authenticatable', domain: 'forgot_password'));

                return $this->redirectToRoute('storefront_signin_identify');
            }
        } else {
            $this->addFlash('error', $this->translator->trans('flash_failed', domain: 'verification_code'));
        }

        return $this->redirectToRoute('storefront_forgot_password_reset', ['identityId' => $identityId]);
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    private function requestPasswordResetCode(Request $request, string $identityId): void
    {
        $retryAt = $this->rateLimiter->consume($request, $identityId, 'reset');
        if (null !== $retryAt) {
            $minutes = (int) ceil(($retryAt->getTimestamp() - $this->clock->now()->getTimestamp()) / 60);
            $this->addFlash('error', $this->translator->trans('flash_rate_limited', ['%minutes%' => $minutes], domain: 'verification_code'));

            return;
        }

        try {
            $this->commandBus->dispatch(new RequestPasswordReset($identityId));
            $this->addFlash('success', $this->translator->trans('flash_sent', domain: 'verification_code'));
        } catch (PasswordResetRequestedTooRecentlyException $e) {
            $seconds = max(1, $e->retryAt->getTimestamp() - $this->clock->now()->getTimestamp());
            $this->addFlash('error', $this->translator->trans('flash_too_recent', ['%seconds%' => $seconds], domain: 'verification_code'));
        }
    }
}
