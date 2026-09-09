<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Command\ApproveOrderErasure;

use Shared\Application\Command\CommandInterface;

final readonly class ApproveOrderErasure implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
