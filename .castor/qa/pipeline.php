<?php

declare(strict_types=1);

use Castor\Attribute\AsTask;

use function Castor\io;

#[AsTask(description: 'Run the full QA pipeline')]
function qa(): void
{
    io()->section('Composer check');
    qa_composer_validate();
    qa_composer_audit();

    io()->section('Static checks');
    qa_static();

    io()->section('Tests (with coverage)');
    qa_test(coverage: true);

    io()->section('Mutation testing');
    qa_mutation(coverage: true);

    io()->success('QA pipeline passed.');
}

#[AsTask(name: 'static', namespace: 'qa', description: 'Run all static checks')]
function qa_static(): void
{
    io()->section('Lint');
    lint();

    io()->section('Coding standards');
    qa_cs(fix: false);

    io()->section('Deptrac');
    qa_deptrac();

    io()->section('PHPStan');
    qa_stan();

    io()->section('Rector');
    qa_rector(fix: false);
}
