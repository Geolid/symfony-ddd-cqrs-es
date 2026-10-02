<?php

declare(strict_types=1);

use Scheb\TwoFactorBundle\SchebTwoFactorBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\UX\Icons\UXIconsBundle;
use Symfony\UX\StimulusBundle\StimulusBundle;
use Symfony\UX\TwigComponent\TwigComponentBundle;
use Zenstruck\Mailer\Test\ZenstruckMailerTestBundle;

return [
    SchebTwoFactorBundle::class => ['all' => true],
    SecurityBundle::class => ['all' => true],
    StimulusBundle::class => ['all' => true],
    TwigBundle::class => ['all' => true],
    TwigComponentBundle::class => ['all' => true],
    UXIconsBundle::class => ['all' => true],
    WebProfilerBundle::class => ['dev' => true],
    ZenstruckMailerTestBundle::class => ['test' => true],
];
