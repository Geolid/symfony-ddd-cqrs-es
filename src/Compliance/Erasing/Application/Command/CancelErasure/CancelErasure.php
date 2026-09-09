<?php

declare(strict_types=1);

namespace Compliance\Erasing\Application\Command\CancelErasure;

use Shared\Application\Command\CommandInterface;

final readonly class CancelErasure implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
