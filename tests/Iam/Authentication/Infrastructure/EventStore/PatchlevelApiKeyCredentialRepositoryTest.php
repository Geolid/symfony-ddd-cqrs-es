<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\EventStore;

use Iam\Authentication\Domain\ApiKeyCredential\Exception\ApiKeyCredentialNotFoundException;
use Iam\Authentication\Domain\ApiKeyCredential\Repository\ApiKeyCredentialRepositoryInterface;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\ApiKeyCredentialId;
use Iam\Tests\Authentication\Support\Doubles\StubApiKeyHasher;
use Iam\Tests\Authentication\Support\Factory\ApiKeyCredentialTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class PatchlevelApiKeyCredentialRepositoryTest extends AbstractIntegrationTestCase
{
    private ApiKeyCredentialRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(ApiKeyCredentialRepositoryInterface::class);
    }

    #[Test]
    public function itSavesAndLoads(): void
    {
        // Given
        $credential = ApiKeyCredentialTestFactory::new()->withHasher(new StubApiKeyHasher())->create();

        // When
        $this->repository->save($credential);

        // Then
        $id = $credential->id;
        self::assertTrue($this->repository->has($id));
        self::assertSame($id->toString(), $this->repository->load($id)->id->toString());
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Given
        $id = ApiKeyCredentialId::generate();

        // Then
        self::assertFalse($this->repository->has($id));
        $this->expectException(ApiKeyCredentialNotFoundException::class);

        // When
        $this->repository->load($id);
    }
}
