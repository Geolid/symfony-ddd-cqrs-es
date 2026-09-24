<?php

declare(strict_types=1);

namespace Storefront\Controller;

use Iam\Identity\Application\Query\GetIdentityByEmail\GetIdentityByEmail;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Storefront\Form\Identify\IdentifyFormData;
use Storefront\Form\Identify\IdentifyType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

#[Route(path: ['en' => '/signin', 'fr' => '/connexion'], name: 'storefront_signin_')]
final class SignInController extends AbstractController
{
    public function __construct(private readonly QueryBusInterface $queryBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route(name: 'identify', methods: ['GET', 'POST'])]
    public function identify(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        $formData = new IdentifyFormData();
        $formData->email = $authenticationUtils->getLastUsername() ?: null;
        $form = $this->createForm(IdentifyType::class, $formData)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, (string) $formData->email);

            $identity = $this->queryBus->ask(new GetIdentityByEmail((string) $formData->email));

            if (null === $identity) {
                return $this->render('signin/create_account.html.twig', ['email' => $formData->email]);
            }

            if (!$identity->verificationStatus->isConfirmed()) {
                return $this->redirectToRoute('storefront_registration_confirm', ['identityId' => $identity->id]);
            }

            return $this->redirectToRoute('storefront_signin_verify');
        }

        return $this->render('signin/identify.html.twig', ['form' => $form]);
    }

    #[Route(path: ['en' => '/verify', 'fr' => '/verifier'], name: 'verify', methods: ['GET', 'POST'])]
    public function verify(AuthenticationUtils $authenticationUtils): Response
    {
        $email = $authenticationUtils->getLastUsername();

        if ('' === $email) {
            return $this->redirectToRoute('storefront_signin_identify');
        }

        return $this->render('signin/verify.html.twig', [
            'email' => $email,
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }
}
