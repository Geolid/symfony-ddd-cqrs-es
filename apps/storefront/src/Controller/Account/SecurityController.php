<?php

declare(strict_types=1);

namespace Storefront\Controller\Account;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Iam\Authentication\Application\Command\ConfirmTotpEnrollment\ConfirmTotpEnrollment;
use Iam\Authentication\Application\Command\EnrollTotp\EnrollTotp;
use Iam\Authentication\Application\Command\RevokeTotp\RevokeTotp;
use Iam\Authentication\Application\Query\GetTotpCredentialByIdentity\GetTotpCredentialByIdentity;
use Iam\Authentication\Application\TotpProvisioning\TotpProvisioningInterface;
use Iam\Authentication\Domain\TotpCredential\Exception\InvalidTotpCodeException;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Storefront\Form\TwoFactorConfirm\TwoFactorConfirmFormData;
use Storefront\Form\TwoFactorConfirm\TwoFactorConfirmType;
use Storefront\Security\PasswordUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route(path: ['en' => '/account/security', 'fr' => '/compte/connexion-securite'], name: 'storefront_account_security_')]
final class SecurityController extends AbstractController
{
    private const string SESSION_KEY = 'storefront.two_factor_enrollment_secret';
    private const string ISSUER = 'Storefront';

    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly CommandBusInterface $commandBus,
        private readonly TotpProvisioningInterface $provisioning,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     */
    #[Route(name: 'show', methods: ['GET'])]
    public function show(#[CurrentUser] PasswordUser $user): Response
    {
        $totpCredential = $this->queryBus->ask(new GetTotpCredentialByIdentity($user->identityId()));

        return $this->render('account/security/show.html.twig', [
            'user' => $user,
            'totpEnrolled' => null !== $totpCredential,
        ]);
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route(path: ['en' => '/2fa/enable', 'fr' => '/2fa/activer'], name: 'enroll_two_factor', methods: ['GET', 'POST'])]
    public function enrollTwoFactor(Request $request, #[CurrentUser] PasswordUser $user): Response
    {
        $session = $request->getSession();
        /** @var non-empty-string|null $secret */
        $secret = $session->get(self::SESSION_KEY);

        if (null === $secret) {
            $secret = $this->provisioning->generateSecret();
            $session->set(self::SESSION_KEY, $secret);
        }

        $provisioningUri = $this->provisioning->provisioningUri($secret, $user->getUserIdentifier(), self::ISSUER);

        $formData = new TwoFactorConfirmFormData();
        $form = $this->createForm(TwoFactorConfirmType::class, $formData)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $id = Uuid::uuid7()->toString();
            $this->commandBus->dispatch(new EnrollTotp($id, $user->identityId(), $secret));

            try {
                $this->commandBus->dispatch(new ConfirmTotpEnrollment($id, $user->identityId(), (string) $formData->code));
            } catch (InvalidTotpCodeException) {
                $this->commandBus->dispatch(new RevokeTotp($id, $user->identityId()));
                $this->addFlash('error', $this->translator->trans('enroll_two_factor_flash_invalid_code', domain: 'account_security'));

                return $this->renderEnrollTwoFactorForm($form, $secret, $provisioningUri);
            }

            $session->remove(self::SESSION_KEY);
            $this->addFlash('success', $this->translator->trans('enroll_two_factor_flash_enabled', domain: 'account_security'));

            return $this->redirectToRoute('storefront_account_show');
        }

        return $this->renderEnrollTwoFactorForm($form, $secret, $provisioningUri);
    }

    /**
     * @param FormInterface<TwoFactorConfirmFormData> $form
     */
    private function renderEnrollTwoFactorForm(FormInterface $form, string $secret, string $provisioningUri): Response
    {
        $qrCode = new Builder()->build(writer: new SvgWriter(), data: $provisioningUri);

        return $this->render('account/security/enroll_two_factor.html.twig', [
            'form' => $form,
            'secret' => $secret,
            'qrCodeDataUri' => $qrCode->getDataUri(),
        ]);
    }
}
