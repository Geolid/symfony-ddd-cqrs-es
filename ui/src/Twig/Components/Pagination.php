<?php

declare(strict_types=1);

namespace Ui\Twig\Components;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(name: 'ui:Pagination', template: '@ui/components/Pagination.html.twig')]
final class Pagination
{
    public int $currentPage;
    public int $lastPage;
    public string $route;

    /** @var array<string, mixed> */
    public array $query = [];

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function hasMultiplePages(): bool
    {
        return $this->lastPage > 1;
    }

    public function hasPrevious(): bool
    {
        return $this->currentPage > 1;
    }

    public function hasNext(): bool
    {
        return $this->currentPage < $this->lastPage;
    }

    public function getPreviousUrl(): string
    {
        return $this->urlGenerator->generate($this->route, [...$this->query, 'page' => $this->currentPage - 1]);
    }

    public function getNextUrl(): string
    {
        return $this->urlGenerator->generate($this->route, [...$this->query, 'page' => $this->currentPage + 1]);
    }

    public function getPageOfLabel(): string
    {
        return $this->translator->trans('pagination.page_of', [
            '%current%' => $this->currentPage,
            '%last%' => $this->lastPage,
        ]);
    }
}
