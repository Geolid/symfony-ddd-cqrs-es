<?php

declare(strict_types=1);

use Castor\Attribute\AsArgument;
use Castor\Attribute\AsTask;

#[AsTask(description: 'Run all linters (optional: a DM name, default: all)')]
function lint(
    #[AsArgument(description: 'Restrict to a single DM (default: every DM)', autocomplete: 'autocompleteApps')]
    ?string $app = null,
): void {
    qaLintContainer($app);
    qaLintTwig($app);
    qaLintTranslations($app);
}

#[AsTask(name: 'container', namespace: 'qa:lint', description: 'Validate Symfony container for all DMs (optional: a DM name, default: all)')]
function qaLintContainer(
    #[AsArgument(description: 'Restrict to a single DM (default: every DM)', autocomplete: 'autocompleteApps')]
    ?string $app = null,
): void {
    foreach (resolveApps($app) as $app) {
        console(['lint:container', '--no-debug', "--appId={$app}"]);
    }
}

#[AsTask(name: 'translations', namespace: 'qa:lint', description: 'Check YAML syntax of translation files (shared + per DM) (optional: a DM name, default: all)')]
function qaLintTranslations(
    #[AsArgument(description: 'Restrict to a single DM (default: every DM)', autocomplete: 'autocompleteApps')]
    ?string $app = null,
): void {
    foreach (resolveApps($app) as $app) {
        if (!is_file(__DIR__."/../../apps/{$app}/config/packages/translation.php")) {
            continue;
        }

        $translationDirs = ['ui/translations'];
        if (is_dir(__DIR__."/../../apps/{$app}/translations")) {
            $translationDirs[] = "apps/{$app}/translations";
        }

        console(['lint:yaml', ...$translationDirs, "--appId={$app}"]);
    }
}

#[AsTask(name: 'twig', namespace: 'qa:lint', description: 'Check Twig syntax for DMs where TwigBundle is loaded (optional: a DM name, default: all)')]
function qaLintTwig(
    #[AsArgument(description: 'Restrict to a single DM (default: every DM)', autocomplete: 'autocompleteApps')]
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

        console(['lint:twig', ...$templateDirs, "--appId={$app}"]);
    }
}
