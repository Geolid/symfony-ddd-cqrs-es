<?php

declare(strict_types=1);

use Castor\Attribute\AsArgument;
use Castor\Attribute\AsTask;

use function Castor\io;

#[AsTask(description: 'Run all linters')]
function lint(
    #[AsArgument(description: 'Restrict to a single DM (default: all)', autocomplete: 'autocompleteApps')]
    ?string $app = null,
): void {
    qaLintContainer($app);
    qaLintTwig($app);
    qaLintTranslations($app);
}

#[AsTask(name: 'container', namespace: 'qa:lint', description: 'Validate Symfony container for all DMs')]
function qaLintContainer(
    #[AsArgument(description: 'Restrict to a single DM (default: all)', autocomplete: 'autocompleteApps')]
    ?string $app = null,
): void {
    foreach (resolveApps($app) as $app) {
        io()->comment("DM: {$app}");

        console(['lint:container', '--no-debug', "--appId={$app}"]);
    }
}

#[AsTask(name: 'translations', namespace: 'qa:lint', description: 'Check YAML syntax of translation files (shared + per DM)')]
function qaLintTranslations(
    #[AsArgument(description: 'Restrict to a single DM (default: all)', autocomplete: 'autocompleteApps')]
    ?string $app = null,
): void {
    preg_match_all('/ui\/translations[^\'"]*/', (string) file_get_contents(__DIR__.'/../../config/packages/translation.php'), $sharedMatches);

    foreach (resolveApps($app) as $app) {
        if (!is_file(__DIR__."/../../apps/{$app}/config/packages/translation.php")) {
            continue;
        }

        $translationDirs = $sharedMatches[0];
        if (is_dir(__DIR__."/../../apps/{$app}/translations")) {
            $translationDirs[] = "apps/{$app}/translations";
        }

        io()->comment("DM: {$app} (".implode(', ', $translationDirs).')');

        console(['lint:yaml', ...$translationDirs, "--appId={$app}"]);
    }
}

#[AsTask(name: 'twig', namespace: 'qa:lint', description: 'Check Twig syntax for DMs where TwigBundle is loaded')]
function qaLintTwig(
    #[AsArgument(description: 'Restrict to a single DM (default: all)', autocomplete: 'autocompleteApps')]
    ?string $app = null,
): void {
    foreach (resolveApps($app) as $app) {
        $configFile = __DIR__."/../../apps/{$app}/config/packages/twig.php";
        if (!is_file($configFile)) {
            continue;
        }

        preg_match_all('/ui\/templates[^\'"]*/', (string) file_get_contents($configFile), $matches);
        $templateDirs = $matches[0];
        if (is_dir(__DIR__."/../../apps/{$app}/templates")) {
            $templateDirs[] = "apps/{$app}/templates";
        }

        io()->comment("DM: {$app} (".implode(', ', $templateDirs).')');

        console(['lint:twig', ...$templateDirs, "--appId={$app}"]);
    }
}
