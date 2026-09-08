<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\DropApiKeyCredentialCipherKey;

use Shared\Application\CipherKey\CipherKeyDropperInterface;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class DropApiKeyCredentialCipherKeyHandler
{
    public function __construct(private CipherKeyDropperInterface $cipherKeyDropper)
    {
    }

    public function __invoke(DropApiKeyCredentialCipherKey $command): void
    {
        $this->cipherKeyDropper->drop($command->id);
    }
}
