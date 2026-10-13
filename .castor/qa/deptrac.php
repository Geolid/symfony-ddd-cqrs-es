<?php

declare(strict_types=1);

use Castor\Attribute\AsArgsAfterOptionEnd;
use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;

use function Castor\io;

/**
 * @param list<string> $args
 */
#[AsTask(name: 'deptrac', namespace: 'qa', description: 'Run architectural checks')]
function qa_deptrac(
    #[AsOption(description: 'Restrict to a single scope (default: all)', autocomplete: 'deptrac_scopes')]
    ?string $scope = null,
    #[AsArgsAfterOptionEnd]
    array $args = [],
): void {
    $allowedScopes = deptrac_scopes();

    assert_one_of($scope, $allowedScopes, 'scope');

    $scopesToRun = $scope ? [$scope] : $allowedScopes;

    foreach ($scopesToRun as $name) {
        io()->comment("Scope: {$name}");

        workspace_exec([
            'vendor/bin/deptrac',
            'analyse',
            "--config-file=deptrac_{$name}.yaml",
            '--fail-on-uncovered',
            '--report-uncovered',
            ...$args,
        ]);
    }
}

/**
 * @return list<string>
 */
function deptrac_scopes(): array
{
    return array_map(static fn (string $file): string => (string) preg_replace('/^deptrac_(.+)\.yaml$/', '$1', basename($file)), glob(__DIR__.'/../../deptrac_*.yaml') ?: []);
}
