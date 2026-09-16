<?php

declare(strict_types=1);

namespace Storefront\Controller;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Iam\Authentication\Application\Command\ConfirmTotpEnrollment\ConfirmTotpEnrollment;
use Iam\Authentication\Application\Command\EnrollTotp\EnrollTotp;
use Iam\Authentication\Domain\TotpCredential\Exception\InvalidTotpCodeException;
use Iam\Authentication\Domain\TotpCredential\Exception\TotpCredentialNotConfirmableException;
use OTPHP\TOTP;
use OTPHP\TOTPInterface;
use Psr\Clock\ClockInterface;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Storefront\Form\FormData\TotpConfirmFormData;
use Storefront\Form\TotpConfirmType;
use Storefront\Security\PasswordUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Webmozart\Assert\Assert;

final class TotpController extends AbstractController
{
    private const string SESSION_KEY = 'storefront.totp_enrollment_draft';

    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route(path: '/compte/totp/activer', name: 'storefront_totp_enroll', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function enroll(Request $request, #[CurrentUser] PasswordUser $user): Response
    {
        $session = $request->getSession();
        /** @var array{id: string, secret: string}|null $draft */
        $draft = $session->get(self::SESSION_KEY);

        if (null === $draft) {
            $draft = ['id' => Uuid::uuid7()->toString(), 'secret' => TOTP::generate($this->clock)->getSecret()];
            $this->commandBus->dispatch(new EnrollTotp($draft['id'], $user->identityId(), $draft['secret']));
            $session->set(self::SESSION_KEY, $draft);
        }

        Assert::stringNotEmpty($draft['secret']);
        $otp = TOTP::createFromSecret($draft['secret'], $this->clock);
        $otp->setLabel($user->getUserIdentifier());
        $otp->setIssuer('Storefront');

        $formData = new TotpConfirmFormData();
        $form = $this->createForm(TotpConfirmType::class, $formData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->commandBus->dispatch(new ConfirmTotpEnrollment($draft['id'], $user->identityId(), (string) $formData->code));
            } catch (InvalidTotpCodeException) {
                $this->addFlash('error', 'Code invalide, réessayez.');

                return $this->renderEnrollForm($form, $otp);
            } catch (TotpCredentialNotConfirmableException) {
                // Already confirmed by an earlier, concurrent submission — nothing left to do.
            }

            $session->remove(self::SESSION_KEY);
            $this->addFlash('success', 'Authentification à deux facteurs activée.');

            return $this->redirectToRoute('storefront_account_show');
        }

        return $this->renderEnrollForm($form, $otp);
    }

    /**
     * @param FormInterface<TotpConfirmFormData> $form
     */
    private function renderEnrollForm(FormInterface $form, TOTPInterface $otp): Response
    {
        $qrCode = new Builder()->build(writer: new SvgWriter(), data: $otp->getProvisioningUri());

        return $this->render('totp/enroll.html.twig', [
            'form' => $form,
            'provisioningUri' => $otp->getProvisioningUri(),
            'qrCodeDataUri' => $qrCode->getDataUri(),
        ]);
    }
}
