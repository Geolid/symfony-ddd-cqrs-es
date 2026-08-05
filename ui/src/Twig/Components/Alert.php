<?php

declare(strict_types=1);

namespace Ui\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(name: 'ui:Alert', template: '@ui/components/Alert.html.twig')]
final class Alert
{
    public string $variant = 'info';

    public function getColorClass(): string
    {
        return match ($this->variant) {
            'success' => 'pico-background-jade-500 pico-color-white',
            'warning' => 'pico-background-amber-500 pico-color-black',
            'error' => 'pico-background-red-600 pico-color-white',
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
