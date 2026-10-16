<?php

declare(strict_types=1);

namespace Storefront\Twig;

use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Attribute\AsTwigFilter;

final readonly class DurationExtension
{
    private const array UNITS = [
        'y' => 'years',
        'm' => 'months',
        'd' => 'days',
        'h' => 'hours',
        'i' => 'minutes',
        's' => 'seconds',
    ];

    public function __construct(private TranslatorInterface $translator)
    {
    }

    #[AsTwigFilter(name: 'duration')]
    public function duration(\DateInterval $interval): string
    {
        foreach (self::UNITS as $property => $unit) {
            if (0 !== $interval->{$property}) {
                return $this->translator->trans($unit, ['count' => $interval->{$property}], 'duration');
            }
        }

        return $this->translator->trans('seconds', ['count' => 0], 'duration');
    }
}
