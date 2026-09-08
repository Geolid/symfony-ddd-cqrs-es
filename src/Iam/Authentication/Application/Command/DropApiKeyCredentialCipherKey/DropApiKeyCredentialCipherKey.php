<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\DropApiKeyCredentialCipherKey;

use Shared\Application\Command\CommandInterface;

final readonly class DropApiKeyCredentialCipherKey implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
