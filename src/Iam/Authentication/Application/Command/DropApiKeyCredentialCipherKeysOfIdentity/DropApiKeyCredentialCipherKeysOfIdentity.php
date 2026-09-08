<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\DropApiKeyCredentialCipherKeysOfIdentity;

use Shared\Application\Command\CommandInterface;

final readonly class DropApiKeyCredentialCipherKeysOfIdentity implements CommandInterface
{
    public function __construct(public string $identityId)
    {
    }
}
