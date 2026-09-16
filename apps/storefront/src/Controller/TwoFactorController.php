<?php

declare(strict_types=1);

namespace Storefront\Controller;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Iam\Authentication\Application\Command\ConfirmTotpEnrollment\ConfirmTotpEnrollment;
use Iam\Authentication\Application\Command\EnrollTotp\EnrollTotp;
use Iam\Authentication\Application\TotpProvisioning\TotpProvisioningInterface;
use Iam\Authentication\Domain\TotpCredential\Exception\InvalidTotpCodeException;
use Iam\Authentication\Domain\TotpCredential\Exception\TotpCredentialNotConfirmableException;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Storefront\Form\FormData\TwoFactorConfirmFormData;
use Storefront\Form\TwoFactorConfirmType;
use Storefront\Security\PasswordUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class TwoFactorController extends AbstractController
{
    private const string SESSION_KEY = 'storefront.two_factor_enrollment_pending';
    private const string ISSUER = 'Storefront';

    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly TotpProvisioningInterface $provisioning,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route(path: '/compte/2fa/activer', name: 'storefront_two_factor_enroll', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function enroll(Request $request, #[CurrentUser] PasswordUser $user): Response
    {
        $session = $request->getSession();
        /** @var array{id: non-empty-string, secret: non-empty-string}|null $pending */
        $pending = $session->get(self::SESSION_KEY);

        if (null === $pending) {
            $pending = ['id' => Uuid::uuid7()->toString(), 'secret' => $this->provisioning->generateSecret()];
            $this->commandBus->dispatch(new EnrollTotp($pending['id'], $user->identityId(), $pending['secret']));
            $session->set(self::SESSION_KEY, $pending);
        }

        $provisioningUri = $this->provisioning->provisioningUri($pending['secret'], $user->getUserIdentifier(), self::ISSUER);

        $formData = new TwoFactorConfirmFormData();
        $form = $this->createForm(TwoFactorConfirmType::class, $formData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->commandBus->dispatch(new ConfirmTotpEnrollment($pending['id'], $user->identityId(), (string) $formData->code));
            } catch (InvalidTotpCodeException) {
                $this->addFlash('error', 'Code invalide, réessayez.');

                return $this->renderEnrollForm($form, $provisioningUri);
            } catch (TotpCredentialNotConfirmableException) {
                // Already confirmed by an earlier, concurrent submission — nothing left to do.
            }

            $session->remove(self::SESSION_KEY);
            $this->addFlash('success', 'Authentification à deux facteurs activée.');

            return $this->redirectToRoute('storefront_account_show');
        }

        return $this->renderEnrollForm($form, $provisioningUri);
    }

    /**
     * @param FormInterface<TwoFactorConfirmFormData> $form
     */
    private function renderEnrollForm(FormInterface $form, string $provisioningUri): Response
    {
        $qrCode = new Builder()->build(writer: new SvgWriter(), data: $provisioningUri);

        return $this->render('two_factor/enroll.html.twig', [
            'form' => $form,
            'provisioningUri' => $provisioningUri,
            'qrCodeDataUri' => $qrCode->getDataUri(),
        ]);
    }
}
