<?php

declare(strict_types=1);
use DAMA\DoctrineTestBundle\DAMADoctrineTestBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Patchlevel\EventSourcingBundle\PatchlevelEventSourcingBundle;
use Sentry\SentryBundle\SentryBundle;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;

return [
    DAMADoctrineTestBundle::class => ['test' => true],
    DoctrineBundle::class => ['all' => true],
    PatchlevelEventSourcingBundle::class => ['all' => true],
    DebugBundle::class => ['dev' => true, 'test' => true],
    FrameworkBundle::class => ['all' => true],
    MonologBundle::class => ['all' => true],
    SentryBundle::class => ['prod' => true],
];
