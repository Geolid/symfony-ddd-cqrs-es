<?php

declare(strict_types=1);

use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;

#[AsTask(name: 'deptrac', namespace: 'qa', description: 'Run architectural checks (optional: --scope=bc|layers|dm, default: all)')]
function qaDeptrac(
    #[AsOption(description: 'Restrict to "bc", "layers" or "dm" (default: all)', autocomplete: ['bc', 'layers', 'dm'])]
    ?string $scope = null,
): void {
    $allowedScopes = ['bc', 'layers', 'dm'];

    if (null !== $scope && !in_array($scope, $allowedScopes, true)) {
        throw new InvalidArgumentException(sprintf('Invalid scope "%s". Allowed values are: %s.', $scope, implode(', ', $allowedScopes)));
    }

    $scopesToRun = $scope ? [$scope] : $allowedScopes;

    foreach ($scopesToRun as $s) {
        dockerExec([
            'vendor/bin/deptrac',
            'analyse',
            "--config-file=deptrac_{$s}.yaml",
            '--fail-on-uncovered',
            '--report-uncovered',
        ]);
    }
}
