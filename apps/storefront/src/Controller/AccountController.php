<?php

declare(strict_types=1);

namespace Storefront\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
#[Route(path: ['en' => '/account', 'fr' => '/compte'], name: 'storefront_account', methods: ['GET'])]
final class AccountController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('account/index.html.twig');
    }
}
