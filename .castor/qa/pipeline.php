<?php

declare(strict_types=1);

use Castor\Attribute\AsTask;

use function Castor\io;

#[AsTask(description: 'Run the full QA pipeline')]
function qa(): void
{
    io()->section('Composer check');
    qaComposerValidate();
    qaComposerAudit();

    io()->section('Static checks');
    qaStatic();

    io()->section('Tests (with coverage)');
    qaTest(coverage: true);

    io()->section('Mutation testing');
    qaMutation(coverage: true);

    io()->success('QA pipeline passed.');
}

#[AsTask(name: 'static', namespace: 'qa', description: 'Run all static checks')]
function qaStatic(): void
{
    io()->section('Lint');
    lint();

    io()->section('Coding standards');
    qaCs(fix: false);

    io()->section('Deptrac');
    qaDeptrac();

    io()->section('PHPStan');
    qaStan();

    io()->section('Rector');
    qaRector(fix: false);
}
