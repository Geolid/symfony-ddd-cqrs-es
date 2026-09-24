<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\RevokeDeviceTrust;

use Shared\Application\Command\CommandInterface;

final readonly class RevokeDeviceTrust implements CommandInterface
{
    public function __construct(public string $identityId)
    {
    }
}
