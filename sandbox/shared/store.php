<?php

declare(strict_types=1);

function fake_api_store_path(string $provider): string
{
    $dir = dirname(__DIR__).'/data';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    return $dir.'/'.$provider.'.json';
}

/**
 * @return array<string, array<string, mixed>>
 */
function fake_api_store_read(string $provider): array
{
    $path = fake_api_store_path($provider);
    if (!file_exists($path)) {
        return [];
    }

    $handle = fopen($path, 'r');
    flock($handle, \LOCK_SH);
    $contents = stream_get_contents($handle);
    flock($handle, \LOCK_UN);
    fclose($handle);

    return json_decode($contents ?: '[]', true, 512, \JSON_THROW_ON_ERROR);
}

/**
 * @param callable(array<string, array<string, mixed>>): array<string, array<string, mixed>> $mutator
 *
 * @return array<string, array<string, mixed>>
 */
function fake_api_store_mutate(string $provider, callable $mutator): array
{
    $handle = fopen(fake_api_store_path($provider), 'c+');
    flock($handle, \LOCK_EX);

    $contents = stream_get_contents($handle);
    $records = json_decode($contents ?: '[]', true, 512, \JSON_THROW_ON_ERROR);
    $records = $mutator($records);

    ftruncate($handle, 0);
    rewind($handle);
    fwrite($handle, json_encode($records, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT));
    fflush($handle);
    flock($handle, \LOCK_UN);
    fclose($handle);

    return $records;
}

/**
 * @return array<string, mixed>|null
 */
function fake_api_store_find(string $provider, string $field, string $value): ?array
{
    foreach (fake_api_store_read($provider) as $record) {
        if (($record[$field] ?? null) === $value) {
            return $record;
        }
    }

    return null;
}
