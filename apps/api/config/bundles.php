<?php

declare(strict_types=1);

return [
    ApiPlatform\Symfony\Bundle\ApiPlatformBundle::class => ['all' => true],
    Patchlevel\EventSourcingAdminBundle\PatchlevelEventSourcingAdminBundle::class => ['demo' => true, 'dev' => true],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
];
