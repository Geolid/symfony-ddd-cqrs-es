<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Gateway\Globex;

use Shared\Infrastructure\Gateway\Globex\Exception\GlobexClientException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class GlobexClient
{
    public function __construct(
        #[Autowire(service: 'globex.client')]
        private HttpClientInterface $client,
    ) {
    }

    /**
     * @param array<string, scalar|array<scalar>> $body
     *
     * @return mixed[]
     *
     * @throws GlobexClientException
     */
    public function post(string $path, array $body): array
    {
        try {
            return $this->client->request('POST', $path, ['json' => $body])->toArray();
        } catch (TransportExceptionInterface|HttpExceptionInterface $e) {
            throw GlobexClientException::networkFailure($path, $e->getMessage());
        } catch (DecodingExceptionInterface $e) {
            throw GlobexClientException::invalidResponse($path, $e->getMessage());
        }
    }
}
