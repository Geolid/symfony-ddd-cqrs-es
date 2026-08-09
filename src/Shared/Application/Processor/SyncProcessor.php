<?php

declare(strict_types=1);

namespace Shared\Application\Processor;

use Patchlevel\EventSourcing\Attribute\Subscriber;
use Patchlevel\EventSourcing\Subscription\RunMode;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class SyncProcessor extends Subscriber
{
    public function __construct(string $id, RunMode $runMode = RunMode::FromNow, string $group = 'sync_processor')
    {
        parent::__construct($id, $runMode, $group);
    }
}
