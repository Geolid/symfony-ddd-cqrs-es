<?php

declare(strict_types=1);

use Castor\Attribute\AsTask;

#[AsTask(name: 'validate', namespace: 'qa:composer', description: 'Validate composer configuration')]
function qa_composer_validate(): void
{
    compose_exec(['composer', 'validate', '--no-check-publish', '--strict']);
}

#[AsTask(name: 'audit', namespace: 'qa:composer', description: 'Check for vulnerable dependencies')]
function qa_composer_audit(): void
{
    compose_exec(['composer', 'audit']);
}
