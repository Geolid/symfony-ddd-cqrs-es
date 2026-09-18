<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\RequestEmailConfirmation;

use Shared\Application\Command\CommandInterface;

final readonly class RequestEmailConfirmation implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
