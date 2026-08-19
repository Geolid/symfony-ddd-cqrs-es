<?php

declare(strict_types=1);

use Castor\Attribute\AsTask;

#[AsTask(name: 'composer-validate', namespace: 'qa', description: 'Validate composer configuration')]
function qaComposerValidate(): void
{
    dockerExec(['composer', 'validate', '--no-check-publish', '--strict']);
}

#[AsTask(name: 'composer-audit', namespace: 'qa', description: 'Check for vulnerable dependencies')]
function qaComposerAudit(): void
{
    dockerExec(['composer', 'audit']);
}
