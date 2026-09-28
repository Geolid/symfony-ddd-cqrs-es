<?php

declare(strict_types=1);

namespace Storefront\Security\Twig;

use DeviceDetector\DeviceDetector;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Attribute\AsTwigFunction;

final readonly class DeviceAgentExtension
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    /**
     * @return array{icon: string, label: string}
     */
    #[AsTwigFunction(name: 'device_agent')]
    public function deviceAgent(string $userAgent): array
    {
        $detector = new DeviceDetector($userAgent);
        $detector->parse();

        $os = $detector->getOs('name');
        $browser = $detector->getClient('name');
        $parts = array_filter([$os, $browser], static fn (mixed $part): bool => \is_string($part) && DeviceDetector::UNKNOWN !== $part);

        return [
            'icon' => $detector->isDesktop() ? 'desktop' : 'mobile',
            'label' => [] === $parts ? $this->translator->trans('trusted_devices_unknown_device', domain: 'account_security') : implode(' ', $parts),
        ];
    }
}
