<?php

declare(strict_types=1);

namespace Storefront\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
#[Route(path: ['en' => '/account', 'fr' => '/compte'], name: 'storefront_account_', methods: ['GET'])]
final class AccountController extends AbstractController
{
    #[Route(name: 'show')]
    public function show(): Response
    {
        return $this->render('account/show.html.twig');
    }
}
