<?php

declare(strict_types=1);

use Castor\Attribute\AsTask;

#[AsTask(name: 'build', namespace: 'ci', description: 'Validate, install dependencies and assets')]
function ci_build(): void
{
    qa_composer_validate();
    setup_vendor();
    setup_assets();
}

#[AsTask(name: 'static', namespace: 'ci', description: 'Audit dependencies then run static analysis')]
function ci_static(): void
{
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
    qa_mutation(skipInitialTests: true);
}
