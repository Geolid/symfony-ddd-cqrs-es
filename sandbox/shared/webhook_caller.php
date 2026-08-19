<?php

declare(strict_types=1);

/**
 * @param array<string, mixed> $payload
 */
function fake_api_call_webhook(string $secretEnvVar, string $signatureHeader, string $eventType, array $payload): void
{
    $body = json_encode($payload, \JSON_THROW_ON_ERROR);
    $signature = 'sha256='.hash_hmac('sha256', $body, (string) getenv($secretEnvVar));

    $context = stream_context_create(['http' => [
        'method' => 'POST',
        'header' => implode("\r\n", [
            'Content-Type: application/json',
            $signatureHeader.': '.$signature,
        ]),
        'content' => $body,
        'ignore_errors' => true,
    ]]);

    @file_get_contents('http://nginx/webhook/webhooks/'.$eventType, false, $context);
    $statusLine = http_get_last_response_headers()[0] ?? '';

    if (!preg_match('/\s(2\d\d)\s/', $statusLine)) {
        throw new RuntimeException(sprintf('Webhook call to "%s" failed: %s', $eventType, $statusLine ?: 'no response'));
    }
}
