<?php

declare(strict_types=1);

namespace Ui\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(name: 'ui:Toast', template: '@ui/components/Toast.html.twig')]
final class Toast
{
    public string $variant = 'info';

    public function getColorClass(): string
    {
        return match ($this->variant) {
            'success' => 'icon-success',
            'warning' => 'icon-warning',
            'error' => 'icon-error',
            default => 'icon-info',
        };
    }

    public function getIcon(): string
    {
        return match ($this->variant) {
            'success' => 'success',
            'warning' => 'warning',
            'error' => 'error',
            default => 'info',
        };
    }
}
