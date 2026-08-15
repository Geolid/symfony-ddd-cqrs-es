<?php

declare(strict_types=1);

use Patchlevel\EventSourcingAdminBundle\PatchlevelEventSourcingAdminBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;

return [
    PatchlevelEventSourcingAdminBundle::class => ['all' => true],
    SecurityBundle::class => ['all' => true],
    TwigBundle::class => ['all' => true],
];
