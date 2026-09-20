<?php

declare(strict_types=1);

namespace Storefront\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: ['en' => '/', 'fr' => '/fr'], name: 'storefront_home', methods: ['GET'])]
final class HomeController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('home/index.html.twig');
    }
}
