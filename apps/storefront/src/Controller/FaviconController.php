<?php

declare(strict_types=1);

namespace Storefront\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FaviconController extends AbstractController
{
    #[Route(path: '/favicon.ico', name: 'storefront_favicon', methods: ['GET'])]
    public function show(): Response
    {
        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
