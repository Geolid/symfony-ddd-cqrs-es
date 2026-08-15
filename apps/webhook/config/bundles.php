<?php

declare(strict_types=1);

use Nelmio\ApiDocBundle\NelmioApiDocBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;

return [
    NelmioApiDocBundle::class => ['all' => true],
    TwigBundle::class => ['all' => true],
];
