<?php

declare(strict_types=1);

use Castor\Attribute\AsOption;
use Castor\Attribute\AsPathArgument;
use Castor\Attribute\AsTask;

use function Castor\with;

#[AsTask(name: 'server', namespace: 'debug', description: 'Start Symfony VarDumper server')]
function debug_server(): void
{
    console(['server:dump']);
}

/**
 * @param list<string> $args
 */
#[AsTask(name: 'test', namespace: 'debug', description: 'Run a test with Xdebug and a visible browser')]
#[NeedsPlaywright]
function debug_test(
    #[AsOption(description: 'Restrict to a single test suite', autocomplete: 'phpunit_testsuites')]
    ?string $testsuite = null,
    #[AsOption(description: 'Filter tests by name')]
    ?string $filter = null,
    #[AsPathArgument(description: 'Test files or directories', filter: '*Test.php')]
    array $args = [],
): void {
    with(static fn () => workspace_exec([
        'vendor/bin/phpunit', '--no-coverage',
        ...(null !== $testsuite ? ['--testsuite', $testsuite] : []),
        ...(null !== $filter ? ['--filter', $filter] : []),
        ...$args,
    ]), context: 'debug');
}
