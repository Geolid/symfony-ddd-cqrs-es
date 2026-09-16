<?php

declare(strict_types=1);

use Scheb\TwoFactorBundle\SchebTwoFactorBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;

return [
    SchebTwoFactorBundle::class => ['all' => true],
    SecurityBundle::class => ['all' => true],
    TwigBundle::class => ['all' => true],
];
