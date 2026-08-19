<?php

declare(strict_types=1);

use Castor\Attribute\AsTask;

#[AsTask(name: 'build', namespace: 'ci', description: 'Validate, install dependencies and assets')]
function ciBuild(): void
{
    qaComposerValidate();
    vendor(dev: false);
    assets();
}

#[AsTask(name: 'static', namespace: 'ci', description: 'Warmup then run static analysis')]
function ciStatic(): void
{
    warmup();
    qaSecurity();
    qaStatic();
}

#[AsTask(name: 'coverage', namespace: 'ci', description: 'Run test suite with coverage')]
function ciCoverage(): void
{
    qaTest(coverage: true);
}

#[AsTask(name: 'mutation', namespace: 'ci', description: 'Run mutation testing')]
function ciMutation(): void
{
    qaMutation(coverage: true);
}
