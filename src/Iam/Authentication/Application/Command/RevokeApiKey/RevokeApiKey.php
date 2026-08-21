<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\RevokeApiKey;

use Shared\Application\Command\CommandInterface;

final readonly class RevokeApiKey implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
