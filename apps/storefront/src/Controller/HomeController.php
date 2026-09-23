<?php

declare(strict_types=1);

namespace Storefront\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: ['en' => '/', 'fr' => '/'], name: 'storefront_home_', methods: ['GET'])]
final class HomeController extends AbstractController
{
    #[Route(name: 'show')]
    public function show(): Response
    {
        return $this->render('home/show.html.twig');
    }
}
