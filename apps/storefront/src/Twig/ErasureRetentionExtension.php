<?php

declare(strict_types=1);

namespace Storefront\Twig;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Attribute\AsTwigFunction;

final readonly class ErasureRetentionExtension
{
    private \DateInterval $retention;

    public function __construct(
        #[Autowire(param: 'compliance.erasing.retention')]
        string $retention,
    ) {
        $this->retention = \DateInterval::createFromDateString($retention);
    }

    #[AsTwigFunction(name: 'erasure_retention')]
    public function retention(): \DateInterval
    {
        return $this->retention;
    }
}
