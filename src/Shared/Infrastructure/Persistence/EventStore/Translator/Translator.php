<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Persistence\EventStore\Translator;

use Patchlevel\EventSourcing\Attribute\Subscriber;
use Patchlevel\EventSourcing\Subscription\RunMode;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Translator extends Subscriber
{
    public const string GROUP = 'translator';

    public function __construct(string $id, string $group = self::GROUP, RunMode $runMode = RunMode::FromBeginning)
    {
        parent::__construct($id, $runMode, $group);
    }
}
