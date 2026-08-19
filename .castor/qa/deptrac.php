<?php

declare(strict_types=1);

use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;

use function Castor\io;

#[AsTask(name: 'deptrac', namespace: 'qa', description: 'Run architectural checks')]
function qaDeptrac(
    #[AsOption(description: 'Restrict to "bc", "layers" or "dm" (default: all)', autocomplete: ['bc', 'layers', 'dm'])]
    ?string $scope = null,
): void {
    $allowedScopes = ['bc', 'layers', 'dm'];

    assertOneOf($scope, $allowedScopes, 'scope');

    $scopesToRun = $scope ? [$scope] : $allowedScopes;

    foreach ($scopesToRun as $s) {
        io()->comment("Scope: {$s}");

        dockerExec([
            'vendor/bin/deptrac',
            'analyse',
            "--config-file=deptrac_{$s}.yaml",
            '--fail-on-uncovered',
            '--report-uncovered',
        ]);
    }
}
