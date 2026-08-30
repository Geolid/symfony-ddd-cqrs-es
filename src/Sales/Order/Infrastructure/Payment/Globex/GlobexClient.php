<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Payment\Globex;

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
    public function post(string $path, array $body, ?string $idempotencyKey = null): array
    {
        $options = ['json' => $body];
        if (null !== $idempotencyKey) {
            $options['headers'] = ['Idempotency-Key' => $idempotencyKey];
        }

        return $this->request('POST', $path, $options);
    }

    /**
     * @return mixed[]
     *
     * @throws GlobexClientException
     */
    public function get(string $path): array
    {
        return $this->request('GET', $path);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return mixed[]
     *
     * @throws GlobexClientException
     */
    private function request(string $method, string $path, array $options = []): array
    {
        try {
            return $this->client->request($method, $path, $options)->toArray();
        } catch (TransportExceptionInterface|HttpExceptionInterface $e) {
            throw GlobexClientException::networkFailure($path, $e->getMessage());
        } catch (DecodingExceptionInterface $e) {
            throw GlobexClientException::invalidResponse($path, $e->getMessage());
        }
    }
}
