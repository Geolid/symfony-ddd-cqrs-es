<?php

declare(strict_types=1);

namespace Shared\Application\Processor;

use Patchlevel\EventSourcing\Attribute\Subscriber;
use Patchlevel\EventSourcing\Subscription\RunMode;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Processor extends Subscriber
{
    public const string GROUP = 'processor';
    public const string GROUP_SYNC = 'sync_processor';

    public function __construct(string $id, bool $sync = false, RunMode $runMode = RunMode::FromNow)
    {
        parent::__construct($id, $runMode, $sync ? self::GROUP_SYNC : self::GROUP);
    }
}
