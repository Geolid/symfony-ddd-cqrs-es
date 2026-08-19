<?php

declare(strict_types=1);

use Castor\Attribute\AsTask;

use function Castor\io;

#[AsTask(description: 'Run the full QA pipeline')]
function qa(): void
{
    io()->section('Composer validate');
    qaComposerValidate();

    io()->section('Static checks (lint, cs, deptrac, stan, rector)');
    qaStatic();

    io()->section('Tests (with coverage)');
    qaTest(coverage: true);

    io()->section('Mutation testing');
    qaMutation(coverage: true);

    io()->success('QA pipeline passed.');
}

#[AsTask(name: 'composer-validate', namespace: 'qa', description: 'Validate composer configuration')]
function qaComposerValidate(): void
{
    dockerExec(['composer', 'validate', '--no-check-publish', '--strict']);
}

#[AsTask(name: 'security', namespace: 'qa', description: 'Check for vulnerable dependencies')]
function qaSecurity(): void
{
    dockerExec(['composer', 'audit']);
}

#[AsTask(name: 'static', namespace: 'qa', description: 'Run linters, coding standards, architecture checks, static analysis and Rector')]
function qaStatic(): void
{
    lint();
    qaCs(fix: false);
    qaDeptrac();
    qaStan();
    qaRector(fix: false);
}
