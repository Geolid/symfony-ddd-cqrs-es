<?php

declare(strict_types=1);

use Patchlevel\EventSourcingDashboardBundle\PatchlevelEventSourcingDashboardBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;

return [
    PatchlevelEventSourcingDashboardBundle::class => ['all' => true],
    SecurityBundle::class => ['all' => true],
    TwigBundle::class => ['all' => true],
];
