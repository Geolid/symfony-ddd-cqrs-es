<?php

declare(strict_types=1);

namespace Finance\Payment\Infrastructure\PSP\Globex;

use Finance\Payment\Application\PSP\PaymentFatalFailureException;
use Finance\Payment\Application\PSP\PaymentGatewayException;
use Finance\Payment\Application\PSP\PaymentTransientFailureException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
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
     * @throws PaymentGatewayException
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
     * @throws PaymentGatewayException
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
     * @throws PaymentGatewayException
     */
    private function request(string $method, string $path, array $options = []): array
    {
        try {
            return $this->client->request($method, $path, $options)->toArray();
        } catch (TransportExceptionInterface|ServerExceptionInterface $e) {
            throw PaymentTransientFailureException::forReason(\sprintf('Globex network failure on "%s": %s', $path, $e->getMessage()), $e);
        } catch (ClientExceptionInterface $e) {
            throw PaymentFatalFailureException::forReason(\sprintf('Globex rejected the payload on "%s": %s', $path, $e->getMessage()), $e);
        } catch (DecodingExceptionInterface $e) {
            throw PaymentFatalFailureException::forReason(\sprintf('Globex invalid response on "%s": %s', $path, $e->getMessage()), $e);
        }
    }
}
