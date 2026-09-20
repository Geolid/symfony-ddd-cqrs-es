<?php

declare(strict_types=1);

namespace Storefront\Controller;

use Iam\Identity\Application\Query\GetIdentityByEmail\GetIdentityByEmail;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Storefront\Form\FormData\IdentifyFormData;
use Storefront\Form\IdentifyType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route(path: ['en' => '/signin', 'fr' => '/connexion'], name: 'storefront_signin', methods: ['GET', 'POST'])]
final class SignInController extends AbstractController
{
    public function __construct(private readonly QueryBusInterface $queryBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     */
    public function __invoke(Request $request, #[MapQueryParameter] ?string $email, AuthenticationUtils $authenticationUtils): Response
    {
        $formData = new IdentifyFormData();
        $formData->email = $email;
        $form = $this->createForm(IdentifyType::class, $formData);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('security/identify.html.twig', ['form' => $form]);
        }

        $identity = $this->queryBus->ask(new GetIdentityByEmail((string) $formData->email));

        if (null === $identity) {
            return $this->render('security/create_account.html.twig', ['email' => $formData->email]);
        }

        if (!$identity->verificationStatus->isConfirmed()) {
            return $this->redirectToRoute('storefront_register_confirm', ['id' => $identity->id, 'email' => $formData->email]);
        }

        return $this->render('security/password.html.twig', [
            'email' => $formData->email,
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }
}
