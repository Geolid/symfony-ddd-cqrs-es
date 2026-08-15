<?php

declare(strict_types=1);
use Patchlevel\EventSourcingAdminBundle\PatchlevelEventSourcingAdminBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;

return [
    PatchlevelEventSourcingAdminBundle::class => ['dev' => true, 'demo' => true],
    TwigBundle::class => ['all' => true],
];
