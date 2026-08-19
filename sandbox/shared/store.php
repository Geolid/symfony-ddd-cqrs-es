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
    if (false === $handle) {
        throw new RuntimeException(sprintf('Unable to open "%s" for reading.', $path));
    }

    flock($handle, \LOCK_SH);
    $contents = stream_get_contents($handle);
    flock($handle, \LOCK_UN);
    fclose($handle);

    $decoded = json_decode($contents ?: '[]', true, 512, \JSON_THROW_ON_ERROR);

    /** @var array<string, array<string, mixed>> $decoded */
    $decoded = is_array($decoded) ? $decoded : [];

    return $decoded;
}

/**
 * @param callable(array<string, array<string, mixed>>): array<string, array<string, mixed>> $mutator
 *
 * @return array<string, array<string, mixed>>
 */
function fake_api_store_mutate(string $provider, callable $mutator): array
{
    $path = fake_api_store_path($provider);
    $handle = fopen($path, 'c+');
    if (false === $handle) {
        throw new RuntimeException(sprintf('Unable to open "%s" for writing.', $path));
    }

    flock($handle, \LOCK_EX);

    $contents = stream_get_contents($handle);
    $records = json_decode($contents ?: '[]', true, 512, \JSON_THROW_ON_ERROR);

    /** @var array<string, array<string, mixed>> $records */
    $records = is_array($records) ? $records : [];
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

/**
 * @return array<string, mixed>|null
 */
function fake_api_find_existing_by_idempotency_key(string $provider, ?string $idempotencyKey): ?array
{
    if (null === $idempotencyKey) {
        return null;
    }

    return fake_api_store_find($provider, 'idempotencyKey', $idempotencyKey);
}

function fake_api_store_transition_status(string $provider, string $reference, string $status): void
{
    fake_api_store_mutate($provider, static function (array $records) use ($reference, $status): array {
        if (isset($records[$reference])) {
            $records[$reference]['status'] = $status;
        }

        return $records;
    });
}
