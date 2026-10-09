<?php

declare(strict_types=1);

use Castor\Attribute\AsArgsAfterOptionEnd;
use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;

use function Castor\capture;
use function Castor\io;

#[AsTask(name: 'lint', namespace: 'qa', description: 'Run all linters')]
function qa_lint(
    #[AsOption(name: 'app-id', description: 'Restrict to a single DM (default: all)', autocomplete: 'apps')]
    ?string $appId = null,
): void {
    qa_lint_container($appId);
    qa_lint_twig($appId);
    qa_lint_translations($appId);
}

/**
 * @param list<string> $args
 */
#[AsTask(name: 'container', namespace: 'qa:lint', description: 'Validate Symfony container for all DMs')]
function qa_lint_container(
    #[AsOption(name: 'app-id', description: 'Restrict to a single DM (default: all)', autocomplete: 'apps')]
    ?string $appId = null,
    #[AsArgsAfterOptionEnd]
    array $args = [],
): void {
    for_each_app($appId, static function (string $app) use ($args): void {
        io()->comment("DM: {$app}");

        console(['lint:container', '--no-debug', ...$args]);
    }, context: 'test');
}

/**
 * @param list<string> $args
 */
#[AsTask(name: 'translations', namespace: 'qa:lint', description: 'Check XLIFF syntax of translation files')]
function qa_lint_translations(
    #[AsOption(name: 'app-id', description: 'Restrict to a single DM (default: all)', autocomplete: 'apps')]
    ?string $appId = null,
    #[AsArgsAfterOptionEnd]
    array $args = [],
): void {
    for_each_app($appId, static function (string $app) use ($args): void {
        $own = "apps/{$app}/translations";

        if (!is_dir(__DIR__.'/../../'.$own)) {
            return;
        }

        /** @var array{'translator.default_path': string, 'kernel.project_dir': string} $parameters */
        $parameters = json_decode(capture(workspace_command(['php', 'bin/console', 'debug:container', '--parameters', '--format=json'])), true, flags: \JSON_THROW_ON_ERROR);
        $shared = substr($parameters['translator.default_path'], strlen($parameters['kernel.project_dir']) + 1);
        $directories = $shared === $own ? [$own] : [$shared, $own];

        io()->comment("DM: {$app} (".implode(', ', $directories).')');

        console(['lint:xliff', ...$directories, ...$args]);
    }, context: 'test');
}

/**
 * @param list<string> $args
 */
#[AsTask(name: 'twig', namespace: 'qa:lint', description: 'Check Twig syntax of template files')]
function qa_lint_twig(
    #[AsOption(name: 'app-id', description: 'Restrict to a single DM (default: all)', autocomplete: 'apps')]
    ?string $appId = null,
    #[AsArgsAfterOptionEnd]
    array $args = [],
): void {
    for_each_app($appId, static function (string $app) use ($args): void {
        $own = "apps/{$app}/templates";

        if (!is_dir(__DIR__.'/../../'.$own)) {
            return;
        }

        /** @var array{loader_paths: array<string, list<string>>} $debug */
        $debug = json_decode(capture(workspace_command(['php', 'bin/console', 'debug:twig', '--format=json'])), true, flags: \JSON_THROW_ON_ERROR);
        $shared = array_filter(array_merge(...array_values($debug['loader_paths'])), static fn (string $path): bool => !str_starts_with($path, 'vendor/') && $path !== $own);
        $directories = [...$shared, $own];

        io()->comment("DM: {$app} (".implode(', ', $directories).')');

        console(['lint:twig', ...$directories, ...$args]);
    }, context: 'test');
}
