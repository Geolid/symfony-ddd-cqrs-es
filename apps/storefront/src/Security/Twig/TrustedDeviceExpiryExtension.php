<?php

declare(strict_types=1);

namespace Storefront\Security\Twig;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Attribute\AsTwigFunction;

final readonly class TrustedDeviceExpiryExtension
{
    public function __construct(
        #[Autowire(param: 'iam.authentication.trusted_device_expiry_seconds')]
        private int $expirySeconds,
    ) {
    }

    #[AsTwigFunction(name: 'trusted_device_expiry_days')]
    public function expiryDays(): int
    {
        return intdiv($this->expirySeconds, 86_400);
    }
}
