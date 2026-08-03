<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Gateway;

use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

final readonly class FakeReferenceResponseFactory
{
    public function __construct(
        private string $referenceField,
        private string $referencePrefix,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function __invoke(string $method, string $url, array $options): ResponseInterface
    {
        $body = $options['body'] ?? $url;
        \assert(\is_string($body));

        $reference = \sprintf('%s-%s', $this->referencePrefix, strtoupper(substr(sha1($body), 0, 8)));

        return new MockResponse(
            json_encode([$this->referenceField => $reference], \JSON_THROW_ON_ERROR),
            ['http_code' => 200, 'response_headers' => ['content-type' => 'application/json']],
        );
    }
}
