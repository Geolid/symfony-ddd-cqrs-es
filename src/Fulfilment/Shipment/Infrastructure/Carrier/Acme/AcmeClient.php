<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Infrastructure\Carrier\Acme;

use Fulfilment\Shipment\Infrastructure\Carrier\Acme\Exception\AcmeClientException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class AcmeClient
{
    public function __construct(
        #[Autowire(service: 'acme.client')]
        private HttpClientInterface $client,
    ) {
    }

    /**
     * @param array<string, scalar|array<scalar>> $body
     *
     * @return mixed[]
     *
     * @throws AcmeClientException
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
     * @throws AcmeClientException
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
     * @throws AcmeClientException
     */
    private function request(string $method, string $path, array $options = []): array
    {
        try {
            return $this->client->request($method, $path, $options)->toArray();
        } catch (TransportExceptionInterface|HttpExceptionInterface $e) {
            throw AcmeClientException::networkFailure($path, $e->getMessage());
        } catch (DecodingExceptionInterface $e) {
            throw AcmeClientException::invalidResponse($path, $e->getMessage());
        }
    }
}
