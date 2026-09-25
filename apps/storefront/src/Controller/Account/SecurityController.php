<?php

declare(strict_types=1);

namespace Storefront\Controller\Account;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Iam\Authentication\Application\BackupCodeRegeneration\BackupCodeRegeneratorInterface;
use Iam\Authentication\Application\Command\RevokeTrustedDevice\RevokeTrustedDevice;
use Iam\Authentication\Application\Command\UnenrollTotp\UnenrollTotp;
use Iam\Authentication\Application\Query\GetBackupCodeCredentialByIdentity\GetBackupCodeCredentialByIdentity;
use Iam\Authentication\Application\Query\GetTotpCredentialByIdentity\GetTotpCredentialByIdentity;
use Iam\Authentication\Application\Query\ListTrustedDevicesByIdentity\ListTrustedDevicesByIdentity;
use Iam\Authentication\Application\TotpEnrollment\TotpEnrollerInterface;
use Iam\Authentication\Application\TotpEnrollment\TotpProvisioningInterface;
use Iam\Authentication\Domain\TotpCredential\Exception\InvalidTotpCodeException;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Storefront\Form\TwoFactorConfirm\TwoFactorConfirmFormData;
use Storefront\Form\TwoFactorConfirm\TwoFactorConfirmType;
use Storefront\Security\PasswordUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
    private const string SESSION_KEY = 'storefront.totp_enrollment_secret';
    private const string ISSUER = 'Storefront';

    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly CommandBusInterface $commandBus,
        private readonly TotpProvisioningInterface $provisioning,
        private readonly TotpEnrollerInterface $totpEnroller,
        private readonly BackupCodeRegeneratorInterface $backupCodeRegenerator,
        private readonly TranslatorInterface $translator,
        #[Autowire(param: 'iam.authentication.trusted_device_lifetime')]
        private readonly int $trustedDeviceLifetime,
    ) {
    }

    #[Route(name: 'show', methods: ['GET'])]
    public function show(#[CurrentUser] PasswordUser $user): Response
    {
        return $this->render('account/security/show.html.twig', ['user' => $user]);
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route(path: ['en' => '/2fa/settings', 'fr' => '/a2f/parametres'], name: 'two_factor_settings', methods: ['GET', 'POST'])]
    public function twoFactorSettings(Request $request, #[CurrentUser] PasswordUser $user): Response
    {
        $totpCredential = $this->queryBus->ask(new GetTotpCredentialByIdentity($user->identityId()));

        if (null === $totpCredential) {
            return $this->enrollTotp($request, $user);
        }

        $backupCodeCredential = $this->queryBus->ask(new GetBackupCodeCredentialByIdentity($user->identityId()));

        return $this->render('account/security/two_factor_settings.html.twig', [
            'backupCodeCredential' => $backupCodeCredential,
        ]);
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route(path: ['en' => '/2fa/devices', 'fr' => '/a2f/appareils'], name: 'trusted_devices', methods: ['GET'])]
    public function trustedDevices(#[CurrentUser] PasswordUser $user): Response
    {
        $trustedDevices = $this->queryBus->ask(new ListTrustedDevicesByIdentity($user->identityId()));

        return $this->render('account/security/trusted_devices.html.twig', [
            'trustedDevices' => $trustedDevices,
            'trustedDeviceLifetimeDays' => intdiv($this->trustedDeviceLifetime, 86400),
        ]);
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route(path: ['en' => '/2fa/devices/{id}/revoke', 'fr' => '/a2f/appareils/{id}/revoquer'], name: 'revoke_trusted_device', methods: ['POST'])]
    public function revokeTrustedDevice(string $id, Request $request, #[CurrentUser] PasswordUser $user): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('revoke_trusted_device_'.$id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $this->commandBus->dispatch(new RevokeTrustedDevice($id, $user->identityId()));

        $this->addFlash('success', $this->translator->trans('trusted_devices_flash_revoked', domain: 'account_security'));

        return $this->redirectToRoute('storefront_account_security_trusted_devices');
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route(path: ['en' => '/2fa/devices/revoke-all', 'fr' => '/a2f/appareils/tout-revoquer'], name: 'revoke_trusted_devices', methods: ['POST'])]
    public function revokeTrustedDevices(Request $request, #[CurrentUser] PasswordUser $user): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('revoke_trusted_devices', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        foreach ($this->queryBus->ask(new ListTrustedDevicesByIdentity($user->identityId())) as $trustedDevice) {
            $this->commandBus->dispatch(new RevokeTrustedDevice($trustedDevice->id, $user->identityId()));
        }

        $this->addFlash('success', $this->translator->trans('trusted_devices_flash_revoked_all', domain: 'account_security'));

        return $this->redirectToRoute('storefront_account_security_trusted_devices');
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route(path: ['en' => '/2fa/disable', 'fr' => '/a2f/desactiver'], name: 'unenroll_totp', methods: ['POST'])]
    public function unenrollTotp(Request $request, #[CurrentUser] PasswordUser $user): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('unenroll_totp', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $totpCredential = $this->queryBus->ask(new GetTotpCredentialByIdentity($user->identityId()));

        if (null !== $totpCredential) {
            $this->commandBus->dispatch(new UnenrollTotp($totpCredential->id, $user->identityId()));
        }

        $this->addFlash('success', $this->translator->trans('two_factor_settings_flash_unenrolled', domain: 'account_security'));

        return $this->redirectToRoute('storefront_account_security_show');
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route(path: ['en' => '/2fa/backup-codes/regenerate', 'fr' => '/a2f/codes-secours/regenerer'], name: 'regenerate_backup_codes', methods: ['POST'])]
    public function regenerateBackupCodes(Request $request, #[CurrentUser] PasswordUser $user): Response
    {
        if (!$this->isCsrfTokenValid('regenerate_backup_codes', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $backupCodes = $this->backupCodeRegenerator->regenerateFor($user->identityId());

        return $this->render('account/security/regenerate_backup_codes.html.twig', ['backupCodes' => $backupCodes]);
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    private function enrollTotp(Request $request, PasswordUser $user): Response
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
            try {
                $backupCodes = $this->totpEnroller->enrollFor($user->identityId(), $secret, (string) $formData->code);
            } catch (InvalidTotpCodeException) {
                $this->addFlash('error', $this->translator->trans('enroll_totp_flash_invalid_code', domain: 'account_security'));

                return $this->renderEnrollTotpForm($form, $secret, $provisioningUri);
            }

            $session->remove(self::SESSION_KEY);

            if (null === $backupCodes) {
                $this->addFlash('success', $this->translator->trans('enroll_totp_flash_enrolled_codes_kept', domain: 'account_security'));

                return $this->redirectToRoute('storefront_account_security_two_factor_settings');
            }

            $this->addFlash('success', $this->translator->trans('enroll_totp_flash_enrolled', domain: 'account_security'));

            return $this->render('account/security/enroll_totp_backup_codes.html.twig', ['backupCodes' => $backupCodes]);
        }

        return $this->renderEnrollTotpForm($form, $secret, $provisioningUri);
    }

    /**
     * @param FormInterface<TwoFactorConfirmFormData> $form
     */
    private function renderEnrollTotpForm(FormInterface $form, string $secret, string $provisioningUri): Response
    {
        $qrCode = new Builder()->build(writer: new SvgWriter(), data: $provisioningUri);

        return $this->render('account/security/enroll_totp.html.twig', [
            'form' => $form,
            'secret' => $secret,
            'qrCodeDataUri' => $qrCode->getDataUri(),
        ]);
    }
}
