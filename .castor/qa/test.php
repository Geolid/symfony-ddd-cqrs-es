<?php

declare(strict_types=1);

use Castor\Attribute\AsArgsAfterOptionEnd;
use Castor\Attribute\AsOption;
use Castor\Attribute\AsPathArgument;
use Castor\Attribute\AsTask;
use Symfony\Component\Console\Input\InputOption;

use function Castor\with;

/**
 * @param list<string> $args
 */
#[AsTask(name: 'test', namespace: 'qa', description: 'Run test suite')]
function qa_test(
    #[AsOption(description: 'Restrict to a single test suite', autocomplete: 'phpunit_testsuites')]
    ?string $testsuite = null,
    #[AsOption(description: 'Filter tests by name')]
    ?string $filter = null,
    #[AsOption(mode: InputOption::VALUE_NONE, description: 'Run with coverage')]
    ?bool $coverage = null,
    #[AsPathArgument(description: 'Test files or directories', filter: '*Test.php')]
    array $args = [],
): void {
    with(static fn () => workspace_exec([
        'vendor/bin/paratest', '--processes', '8',
        ...($coverage ? [] : ['--no-coverage']),
        ...(null !== $testsuite ? ['--testsuite', $testsuite] : []),
        ...(null !== $filter ? ['--filter', $filter] : []),
        ...$args,
    ]), context: 'test');
}

/**
 * @param list<string> $args
 */
#[AsTask(name: 'mutation', namespace: 'qa', description: 'Run mutation testing scoped to the diff')]
function qa_mutation(
    #[AsOption(mode: InputOption::VALUE_NONE, description: 'Reuse var/coverage from `castor qa:test --coverage` and skip initial tests')]
    ?bool $skipInitialTests = null,
    #[AsArgsAfterOptionEnd]
    array $args = [],
): void {
    with(static fn () => workspace_exec([
        'vendor/bin/infection', '--git-diff-lines', '--git-diff-base=origin/main',
        ...($skipInitialTests ? ['--skip-initial-tests', '--coverage=var/coverage'] : []),
        ...$args,
    ]), context: 'test');
}

/**
 * @return list<string>
 */
function phpunit_testsuites(): array
{
    $xml = (string) preg_replace('/<!--.*?-->/s', '', (string) file_get_contents(__DIR__.'/../../phpunit.dist.xml'));
    preg_match_all('/<testsuite\s+name="([^"]+)"/', $xml, $matches);

    return $matches[1];
}
