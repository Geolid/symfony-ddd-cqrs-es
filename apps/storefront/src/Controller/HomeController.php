<?php

declare(strict_types=1);

namespace Storefront\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(name: 'storefront_home_')]
final class HomeController extends AbstractController
{
    /**
     * @param list<string> $enabledLocales
     */
    public function __construct(
        #[Autowire(param: 'kernel.enabled_locales')]
        private readonly array $enabledLocales,
        #[Autowire(param: 'kernel.default_locale')]
        private readonly string $defaultLocale,
    ) {
    }

    #[Route(path: '/', name: 'root', methods: ['GET'])]
    public function root(Request $request): RedirectResponse
    {
        $locale = $request->getPreferredLanguage($this->enabledLocales) ?? $this->defaultLocale;

        return $this->redirectToRoute('storefront_home_show', ['_locale' => $locale]);
    }

    #[Route(path: ['en' => '/home', 'fr' => '/accueil'], name: 'show', methods: ['GET'])]
    public function show(): Response
    {
        return $this->render('home/show.html.twig');
    }
}
