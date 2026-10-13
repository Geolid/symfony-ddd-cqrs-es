<?php

declare(strict_types=1);

use Castor\Attribute\AsTask;

#[AsTask(name: 'composer', namespace: 'qa', description: 'Run all Composer checks')]
function qa_composer(): void
{
    qa_composer_validate();
    qa_composer_audit();
}

#[AsTask(name: 'validate', namespace: 'qa:composer', description: 'Validate composer configuration')]
function qa_composer_validate(): void
{
    workspace_exec(['composer', 'validate', '--no-check-publish', '--strict']);
}

#[AsTask(name: 'audit', namespace: 'qa:composer', description: 'Check for vulnerable dependencies')]
function qa_composer_audit(): void
{
    workspace_exec(['composer', 'audit']);
}
