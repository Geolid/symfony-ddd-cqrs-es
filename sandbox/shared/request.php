<?php

declare(strict_types=1);

function fake_api_read_raw_body(): string
{
    return file_get_contents('php://input') ?: '';
}

/**
 * @return array<string, mixed>
 */
function fake_api_decode_json_body(string $rawBody): array
{
    $body = json_decode($rawBody ?: '[]', true, 512, \JSON_THROW_ON_ERROR);

    return is_array($body) ? $body : [];
}

function fake_api_read_idempotency_key(): ?string
{
    if (!isset($_SERVER['HTTP_IDEMPOTENCY_KEY'])) {
        return null;
    }

    return filter_var($_SERVER['HTTP_IDEMPOTENCY_KEY'], \FILTER_UNSAFE_RAW) ?: null;
}
