<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Gateway;

use Shared\Infrastructure\Gateway\Exception\GraphQlClientException;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Generic GraphQL client reused by every external-vendor gateway (see
 * Catalog\Book\Infrastructure\Gateway\Insights for a concrete example). Each gateway injects
 * its own scoped HttpClientInterface (base URI/auth configured in config/packages/http_client.php)
 * so this class stays vendor-agnostic.
 */
final readonly class GraphQlClient
{
    public function __construct(
        private HttpClientInterface $client,
        private string $vendor,
    ) {
    }

    /**
     * @param array<string, scalar|array<scalar>> $variables
     *
     * @return mixed[]
     */
    public function query(string $query, array $variables): array
    {
        $body = [
            'query' => $query,
            'variables' => $variables,
        ];

        if (preg_match('/(query|mutation) (?<operationName>.*)\s?\(/mU', $query, $matches)) {
            $body['operationName'] = trim($matches['operationName']);
        }

        try {
            $response = $this->client->request('POST', '', [
                'json' => $body,
            ])->toArray();
        } catch (TransportExceptionInterface $e) {
            throw GraphQlClientException::networkFailure($this->vendor, $query, $variables, $e->getMessage());
        } catch (DecodingExceptionInterface $e) {
            throw GraphQlClientException::invalidResponse($this->vendor, $query, $variables, $e->getMessage());
        }

        if (isset($response['errors'])) {
            throw GraphQlClientException::graphQlError($this->vendor, $query, $variables, $response['errors']);
        }

        return $response;
    }
}
