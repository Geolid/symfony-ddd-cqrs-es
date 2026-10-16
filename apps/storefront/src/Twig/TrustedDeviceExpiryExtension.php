<?php

declare(strict_types=1);

namespace Storefront\Twig;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Attribute\AsTwigFunction;

final readonly class TrustedDeviceExpiryExtension
{
    private \DateInterval $expiry;

    public function __construct(
        #[Autowire(param: 'iam.authentication.trusted_device_expiry')]
        string $expiry,
    ) {
        $this->expiry = \DateInterval::createFromDateString($expiry);
    }

    #[AsTwigFunction(name: 'trusted_device_expiry')]
    public function expiry(): \DateInterval
    {
        return $this->expiry;
    }
}
