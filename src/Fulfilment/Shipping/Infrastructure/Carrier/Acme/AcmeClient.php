<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Infrastructure\Carrier\Acme;

use Fulfilment\Shipping\Application\Carrier\CarrierFatalFailureException;
use Fulfilment\Shipping\Application\Carrier\CarrierGatewayException;
use Fulfilment\Shipping\Application\Carrier\CarrierTransientFailureException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
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
     * @throws CarrierGatewayException
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
     * @throws CarrierGatewayException
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
     * @throws CarrierGatewayException
     */
    private function request(string $method, string $path, array $options = []): array
    {
        try {
            return $this->client->request($method, $path, $options)->toArray();
        } catch (TransportExceptionInterface|ServerExceptionInterface $e) {
            throw CarrierTransientFailureException::forReason(\sprintf('Acme network failure on "%s": %s', $path, $e->getMessage()), $e);
        } catch (ClientExceptionInterface $e) {
            throw CarrierFatalFailureException::forReason(\sprintf('Acme rejected the payload on "%s": %s', $path, $e->getMessage()), $e);
        } catch (DecodingExceptionInterface $e) {
            throw CarrierFatalFailureException::forReason(\sprintf('Acme invalid response on "%s": %s', $path, $e->getMessage()), $e);
        }
    }
}
