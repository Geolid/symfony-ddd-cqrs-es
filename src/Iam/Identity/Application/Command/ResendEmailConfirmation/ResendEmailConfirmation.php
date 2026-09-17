<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\ResendEmailConfirmation;

use Shared\Application\Command\CommandInterface;

final readonly class ResendEmailConfirmation implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
