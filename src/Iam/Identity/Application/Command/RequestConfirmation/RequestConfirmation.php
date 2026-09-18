<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\RequestConfirmation;

use Shared\Application\Command\CommandInterface;

final readonly class RequestConfirmation implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
