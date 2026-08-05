<?php

declare(strict_types=1);

function fake_api_reference(string $prefix, string $body): string
{
    return sprintf('%s-%s', $prefix, strtoupper(substr(sha1($body), 0, 8)));
}

function fake_api_respond(array $payload): void
{
    header('Content-Type: application/json');
    echo json_encode($payload, \JSON_THROW_ON_ERROR);
}
