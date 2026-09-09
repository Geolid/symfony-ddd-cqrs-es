<?php

declare(strict_types=1);

namespace Compliance\Erasing\Application\Command\ApproveErasure;

use Shared\Application\Command\CommandInterface;

final readonly class ApproveErasure implements CommandInterface
{
    public function __construct(public string $identityId)
    {
    }
}
