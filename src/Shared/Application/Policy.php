<?php

declare(strict_types=1);

namespace Shared\Application;

use Patchlevel\EventSourcing\Attribute\Subscriber;
use Patchlevel\EventSourcing\Subscription\RunMode;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Policy extends Subscriber
{
    public const string GROUP = 'policy';

    public function __construct(string $id, string $group = self::GROUP, RunMode $runMode = RunMode::FromNow)
    {
        parent::__construct($id, $runMode, $group);
    }
}
