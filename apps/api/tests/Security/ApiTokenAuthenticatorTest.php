<?php

declare(strict_types=1);

namespace Api\Tests\Security;

use Api\Tests\Support\AbstractApiTestCase;
use Iam\Tests\Identity\Support\Factory\ApiTokenCredentialTestFactory;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

final class ApiTokenAuthenticatorTest extends AbstractApiTestCase
{
    #[Test]
    public function itAcceptsARequestWithoutAnyAuthorizationHeader(): void
    {
        // Given
        $client = self::jsonClient();

        // When
        $client->request('GET', '/v1/sales/orders');

        // Then
        self::assertResponseIsSuccessful();
    }

    #[Test]
    public function itAcceptsARequestWithAValidBearerToken(): void
    {
        // Given
        $client = self::jsonClient();
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $this->store(ApiTokenCredentialTestFactory::new()
            ->forIdentity($identity->id()->toString())
            ->withIdentifier('key_abc123')
            ->withSecret('super-secret')
            ->create());

        // When
        $client->request('GET', '/v1/sales/orders', ['headers' => ['Authorization' => 'Bearer key_abc123.super-secret']]);

        // Then
        self::assertResponseIsSuccessful();
    }

    #[Test]
    public function itRejectsAMalformedBearerToken(): void
    {
        // Given
        $client = self::jsonClient();

        // When
        $client->request('GET', '/v1/sales/orders', ['headers' => ['Authorization' => 'Bearer no-dot-here']]);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    #[Test]
    public function itRejectsAnInvalidBearerToken(): void
    {
        // Given
        $client = self::jsonClient();
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $this->store(ApiTokenCredentialTestFactory::new()
            ->forIdentity($identity->id()->toString())
            ->withIdentifier('key_abc123')
            ->withSecret('super-secret')
            ->create());

        // When
        $client->request('GET', '/v1/sales/orders', ['headers' => ['Authorization' => 'Bearer key_abc123.wrong-secret']]);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
}
