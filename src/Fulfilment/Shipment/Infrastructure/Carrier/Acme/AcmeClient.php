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
    public function post(string $path, array $body): array
    {
        try {
            return $this->client->request('POST', $path, ['json' => $body])->toArray();
        } catch (TransportExceptionInterface|HttpExceptionInterface $e) {
            throw AcmeClientException::networkFailure($path, $e->getMessage());
        } catch (DecodingExceptionInterface $e) {
            throw AcmeClientException::invalidResponse($path, $e->getMessage());
        }
    }
}
