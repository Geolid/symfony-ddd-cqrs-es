<?php

declare(strict_types=1);

use Castor\Attribute\AsTask;

#[AsTask(name: 'build', namespace: 'ci', description: 'Validate, install dependencies and assets')]
function ci_build(): void
{
    qa_composer_validate();
    vendor();
    assets();
}

#[AsTask(name: 'static', namespace: 'ci', description: 'Warmup then run static analysis')]
function ci_static(): void
{
    warmup();
    qa_composer_audit();
    qa_static();
}

#[AsTask(name: 'coverage', namespace: 'ci', description: 'Run test suite with coverage')]
function ci_coverage(): void
{
    qa_test(coverage: true);
}

#[AsTask(name: 'mutation', namespace: 'ci', description: 'Run mutation testing')]
function ci_mutation(): void
{
    qa_mutation(coverage: true);
}
