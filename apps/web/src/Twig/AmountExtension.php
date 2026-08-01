<?php

declare(strict_types=1);

namespace Web\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class AmountExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [new TwigFilter('amount', $this->amount(...))];
    }

    public function amount(?int $amountInCents): string
    {
        if (null === $amountInCents) {
            return '—';
        }

        return number_format($amountInCents / 100, 2);
    }
}
