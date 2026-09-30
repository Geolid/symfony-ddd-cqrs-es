<?php

declare(strict_types=1);

use FriendsOfBehat\SymfonyExtension\Bundle\FriendsOfBehatSymfonyExtensionBundle;
use Scheb\TwoFactorBundle\SchebTwoFactorBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\UX\Icons\UXIconsBundle;
use Symfony\UX\StimulusBundle\StimulusBundle;
use Symfony\UX\TwigComponent\TwigComponentBundle;

return [
    FriendsOfBehatSymfonyExtensionBundle::class => ['e2e' => true],
    SchebTwoFactorBundle::class => ['all' => true],
    SecurityBundle::class => ['all' => true],
    StimulusBundle::class => ['all' => true],
    TwigBundle::class => ['all' => true],
    TwigComponentBundle::class => ['all' => true],
    UXIconsBundle::class => ['all' => true],
];
