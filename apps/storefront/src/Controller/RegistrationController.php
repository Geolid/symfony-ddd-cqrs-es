<?php

declare(strict_types=1);

namespace Storefront\Controller;

use Iam\Authentication\Application\Command\DefinePassword\DefinePassword;
use Iam\Identity\Application\Command\ConfirmIdentity\ConfirmIdentity;
use Iam\Identity\Application\Command\RegisterIdentity\Exception\IdentityEmailAlreadyInUseException;
use Iam\Identity\Application\Command\RegisterIdentity\RegisterIdentity;
use Iam\Identity\Application\Command\RequestIdentityConfirmation\RequestIdentityConfirmation;
use Iam\Identity\Domain\Exception\ConfirmationRequestedTooRecentlyException;
use Iam\Identity\Domain\Exception\IdentityAlreadyConfirmedException;
use Psr\Clock\ClockInterface;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Storefront\Form\Confirmation\ConfirmationFormData;
use Storefront\Form\Confirmation\ConfirmationType;
use Storefront\Form\FormExceptionMapper;
use Storefront\Form\Register\RegisterFormData;
use Storefront\Form\Register\RegisterType;
use Storefront\Security\RateLimiter\VerificationCodeRateLimiter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: ['en' => '/register', 'fr' => '/inscription'], name: 'storefront_registration_')]
final class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
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
    #[Route(name: 'register', methods: ['GET', 'POST'])]
    public function register(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        $email = $authenticationUtils->getLastUsername();

        if ('' === $email) {
            return $this->redirectToRoute('storefront_signin_identify');
        }

        $formData = new RegisterFormData();
        $formData->email = $email;
        $form = $this->createForm(RegisterType::class, $formData)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $identityId = Uuid::uuid7()->toString();

            try {
                $this->commandBus->dispatch(new RegisterIdentity($identityId, (string) $formData->fullName, $formData->email));
            } catch (IdentityEmailAlreadyInUseException) {
                return $this->redirectToRoute('storefront_signin_identify');
            }

            $this->commandBus->dispatch(new DefinePassword($identityId, (string) $formData->password));

            return $this->redirectToRoute('storefront_registration_confirm', ['identityId' => $identityId]);
        }

        return $this->render('registration/register.html.twig', ['form' => $form]);
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route(path: ['en' => '/{identityId}/confirm', 'fr' => '/{identityId}/confirmation'], name: 'confirm', requirements: ['identityId' => Requirement::UUID_V7], methods: ['GET', 'POST'])]
    public function confirm(Request $request, string $identityId, AuthenticationUtils $authenticationUtils): Response
    {
        $formData = new ConfirmationFormData();
        $form = $this->createForm(ConfirmationType::class, $formData)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->commandBus->dispatch(new ConfirmIdentity($identityId, (string) $formData->code));
                $this->rateLimiter->reset($request, $identityId, 'confirm');
                $this->addFlash('success', $this->translator->trans('confirm_flash_confirmed', domain: 'registration'));

                return $this->redirectToRoute('storefront_signin_identify');
            } catch (ApplicationExceptionInterface|\DomainException $e) {
                if (!$this->formExceptionMapper->map($form, $e)) {
                    throw $e;
                }
            }
        }

        return $this->render('registration/confirm.html.twig', [
            'form' => $form,
            'identityId' => $identityId,
            'email' => $authenticationUtils->getLastUsername(),
        ]);
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route(path: ['en' => '/{identityId}/confirm/resend', 'fr' => '/{identityId}/confirmation/renvoyer'], name: 'confirm_resend', requirements: ['identityId' => Requirement::UUID_V7], methods: ['POST'])]
    public function confirmResend(Request $request, string $identityId): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('confirmation_resend', (string) $request->request->get('_token'))) {
            $this->addFlash('error', $this->translator->trans('flash_failed', domain: 'verification_code'));

            return $this->redirectToRoute('storefront_registration_confirm', ['identityId' => $identityId]);
        }

        $retryAt = $this->rateLimiter->consume($request, $identityId, 'confirm');
        if (null !== $retryAt) {
            $minutes = (int) ceil(($retryAt->getTimestamp() - $this->clock->now()->getTimestamp()) / 60);
            $this->addFlash('error', $this->translator->trans('flash_rate_limited', ['%minutes%' => $minutes], domain: 'verification_code'));

            return $this->redirectToRoute('storefront_registration_confirm', ['identityId' => $identityId]);
        }

        try {
            $this->commandBus->dispatch(new RequestIdentityConfirmation($identityId));
            $this->addFlash('success', $this->translator->trans('flash_sent', domain: 'verification_code'));
        } catch (ConfirmationRequestedTooRecentlyException $e) {
            $seconds = max(1, $e->retryAt->getTimestamp() - $this->clock->now()->getTimestamp());
            $this->addFlash('error', $this->translator->trans('flash_too_recent', ['%seconds%' => $seconds], domain: 'verification_code'));
        } catch (IdentityAlreadyConfirmedException) {
            $this->addFlash('success', $this->translator->trans('confirm_resend_flash_already_confirmed', domain: 'registration'));

            return $this->redirectToRoute('storefront_signin_identify');
        }

        return $this->redirectToRoute('storefront_registration_confirm', ['identityId' => $identityId]);
    }
}
