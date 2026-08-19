<?php

declare(strict_types=1);

use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;

use function Castor\io;

#[AsTask(name: 'deptrac', namespace: 'qa', description: 'Run architectural checks')]
function qa_deptrac(
    #[AsOption(description: 'Restrict to "bc", "layers" or "dm" (default: all)', autocomplete: ['bc', 'layers', 'dm'])]
    ?string $scope = null,
): void {
    $allowedScopes = ['bc', 'layers', 'dm'];

    assert_one_of($scope, $allowedScopes, 'scope');

    $scopesToRun = $scope ? [$scope] : $allowedScopes;

    foreach ($scopesToRun as $s) {
        io()->comment("Scope: {$s}");

        docker_exec([
            'vendor/bin/deptrac',
            'analyse',
            "--config-file=deptrac_{$s}.yaml",
            '--fail-on-uncovered',
            '--report-uncovered',
        ]);
    }
}
