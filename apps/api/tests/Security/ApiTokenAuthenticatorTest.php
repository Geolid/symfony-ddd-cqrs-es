<?php

declare(strict_types=1);

namespace Api\Tests\Security;

use Api\Tests\Support\AbstractApiTestCase;
use Iam\Tests\Access\Support\Factory\GrantTestFactory;
use Iam\Tests\Identity\Support\Factory\ApiTokenCredentialTestFactory;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class ApiTokenAuthenticatorTest extends AbstractApiTestCase
{
    #[Test]
    public function itRejectsARequestWithoutAnyApiKeyHeader(): void
    {
        // Given
        $client = self::jsonClient();
        $client->getKernelBrowser()->catchExceptions(false);

        // When
        $client->request('GET', '/v1/sales/orders');

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    #[Test]
    public function itAcceptsARequestWithAValidApiKeyAndGrant(): void
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
        $this->store(GrantTestFactory::new()->forIdentity($identity->id()->toString())->withPermission('sales:read')->create());

        // When
        $client->request('GET', '/v1/sales/orders', ['headers' => ['X-Api-Key' => 'key_abc123.super-secret']]);

        // Then
        self::assertResponseIsSuccessful();
    }

    #[Test]
    public function itGrantsAccessToThePermissionsHeldByTheIdentity(): void
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
        $this->store(GrantTestFactory::new()->forIdentity($identity->id()->toString())->withPermission('sales:read')->create());

        // When
        $client->request('GET', '/v1/sales/orders', ['headers' => ['X-Api-Key' => 'key_abc123.super-secret']]);

        // Then
        $authorizationChecker = $this->service(AuthorizationCheckerInterface::class);
        self::assertTrue($authorizationChecker->isGranted('sales:read'));
        self::assertFalse($authorizationChecker->isGranted('catalog:manage'));
    }

    #[Test]
    public function itRejectsAValidApiKeyWithoutTheRequiredGrant(): void
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
        $client->request('GET', '/v1/sales/orders', ['headers' => ['X-Api-Key' => 'key_abc123.super-secret']]);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    #[Test]
    public function itRejectsAMalformedApiKey(): void
    {
        // Given
        $client = self::jsonClient();

        // When
        $client->request('GET', '/v1/sales/orders', ['headers' => ['X-Api-Key' => 'no-dot-here']]);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    #[Test]
    public function itRejectsAnInvalidApiKey(): void
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
        $client->request('GET', '/v1/sales/orders', ['headers' => ['X-Api-Key' => 'key_abc123.wrong-secret']]);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
}
