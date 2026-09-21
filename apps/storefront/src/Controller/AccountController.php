<?php

declare(strict_types=1);

namespace Storefront\Controller;

use Iam\Authentication\Application\Query\GetTotpCredentialByIdentity\GetTotpCredentialByIdentity;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Storefront\Security\PasswordUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class AccountController extends AbstractController
{
    public function __construct(private readonly QueryBusInterface $queryBus)
    {
    }

    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    #[Route(path: ['en' => '/account', 'fr' => '/compte'], name: 'storefront_account_show', methods: ['GET'])]
    public function show(): Response
    {
        return $this->render('account/show.html.twig');
    }

    /**
     * @throws ApplicationExceptionInterface
     */
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route(path: ['en' => '/account/security', 'fr' => '/compte/connexion-securite'], name: 'storefront_account_security', methods: ['GET'])]
    public function security(#[CurrentUser] PasswordUser $user): Response
    {
        $totpCredential = $this->queryBus->ask(new GetTotpCredentialByIdentity($user->identityId()));

        return $this->render('account/security.html.twig', [
            'user' => $user,
            'totpEnrolled' => null !== $totpCredential,
        ]);
    }
}
