<?php

declare(strict_types=1);

use Castor\Attribute\AsTask;

use function Castor\io;

#[AsTask(description: 'Run the full QA pipeline')]
#[NeedsPlaywright]
#[NeedsWarmup]
function qa(): void
{
    io()->section('Composer check');
    qa_composer();

    io()->section('Static checks');
    qa_static();

    io()->section('Tests (with coverage)');
    qa_test(coverage: true);

    io()->section('Mutation testing');
    qa_mutation(skipInitialTests: true);

    io()->success('QA pipeline passed');
}

#[AsTask(name: 'static', namespace: 'qa', description: 'Run all static checks')]
#[NeedsWarmup]
function qa_static(): void
{
    io()->section('Lint');
    qa_lint();

    io()->section('Coding standards');
    qa_cs();

    io()->section('Deptrac');
    qa_deptrac();

    io()->section('PHPStan');
    qa_stan();

    io()->section('Rector');
    qa_rector();
}
