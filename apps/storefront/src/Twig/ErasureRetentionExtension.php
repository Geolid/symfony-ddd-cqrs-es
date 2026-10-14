<?php

declare(strict_types=1);

namespace Storefront\Twig;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Attribute\AsTwigFunction;

final readonly class ErasureRetentionExtension
{
    public function __construct(
        #[Autowire(param: 'compliance.erasing.retention_seconds')]
        private int $retentionSeconds,
    ) {
    }

    #[AsTwigFunction(name: 'erasure_retention_days')]
    public function retentionDays(): int
    {
        return intdiv($this->retentionSeconds, 86_400);
    }
}
