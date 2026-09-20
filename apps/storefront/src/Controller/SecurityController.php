<?php

declare(strict_types=1);

namespace Storefront\Controller;

use Iam\Identity\Application\Query\GetIdentityByEmail\GetIdentityByEmail;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Storefront\Controller\QueryString\LoginQueryString;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class SecurityController extends AbstractController
{
    public function __construct(private readonly QueryBusInterface $queryBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     */
    #[Route(path: '/login', name: 'security_login', methods: ['GET', 'POST'])]
    public function login(#[MapQueryString] LoginQueryString $query, AuthenticationUtils $authenticationUtils): Response
    {
        if (null === $query->email || '' === $query->email) {
            return $this->render('security/identify.html.twig');
        }

        $identity = $this->queryBus->ask(new GetIdentityByEmail($query->email));

        if (null === $identity) {
            return $this->render('security/unknown.html.twig', ['email' => $query->email]);
        }

        if (!$identity->verificationStatus->isConfirmed()) {
            return $this->redirectToRoute('storefront_register_confirm', ['id' => $identity->id]);
        }

        return $this->render('security/password.html.twig', [
            'email' => $query->email,
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }
}
