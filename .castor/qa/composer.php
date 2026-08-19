<?php

declare(strict_types=1);

use Castor\Attribute\AsTask;

#[AsTask(name: 'composer-validate', namespace: 'qa', description: 'Validate composer configuration')]
function qa_composer_validate(): void
{
    docker_exec(['composer', 'validate', '--no-check-publish', '--strict']);
}

#[AsTask(name: 'composer-audit', namespace: 'qa', description: 'Check for vulnerable dependencies')]
function qa_composer_audit(): void
{
    docker_exec(['composer', 'audit']);
}
