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
            'success' => 'pico-color-jade-500',
            'warning' => 'pico-color-amber-500',
            'error' => 'pico-color-red-600',
            default => '',
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
