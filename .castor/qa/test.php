<?php

declare(strict_types=1);

use Castor\Attribute\AsArgument;
use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;
use Castor\Context;
use Symfony\Component\Console\Input\InputOption;

use function Castor\with;

#[AsTask(name: 'test', namespace: 'qa', description: 'Run test suite')]
function qaTest(
    #[AsOption(description: 'Filter tests by name')]
    ?string $filter = null,
    #[AsOption(description: 'Run a specific test suite')]
    ?string $suite = null,
    #[AsArgument(description: 'Target test file or directory')]
    ?string $target = null,
    #[AsOption(mode: InputOption::VALUE_NONE, description: 'Run with coverage')]
    ?bool $coverage = null,
): void {
    // No APP_ENV forwarded: phpunit.dist.xml forces it to "test" itself, and that
    // force loses to a real, externally-set APP_ENV env var.
    with(static fn () => dockerExec([
        'vendor/bin/paratest', '--processes', '8', '--display-all-issues',
        ...(!$coverage ? ['--no-coverage'] : []),
        ...(null !== $filter ? ['--filter', $filter] : []),
        ...(null !== $suite ? ['--testsuite', $suite] : []),
        ...(null !== $target ? [$target] : []),
    ]), context: new Context());
}

#[AsTask(name: 'mutation', namespace: 'qa', description: 'Run mutation testing scoped to the diff')]
function qaMutation(
    #[AsOption(mode: InputOption::VALUE_NONE, description: 'Reuse var/coverage from `castor qa:test --coverage` and skip initial tests')]
    ?bool $coverage = null,
): void {
    with(static fn () => dockerExec([
        'vendor/bin/infection', '--threads=max', '--git-diff-lines', '--git-diff-base=origin/main',
        '--min-msi=100', '--ignore-msi-with-no-mutations',
        ...($coverage ? ['--coverage=var/coverage', '--skip-initial-tests'] : []),
    ]), context: new Context());
}
