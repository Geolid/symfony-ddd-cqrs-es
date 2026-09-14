<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Projection\Finder;

use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialFinderInterface;
use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialResult;
use Iam\Authentication\Application\Finder\ApiKeyCredential\Exception\ApiKeyCredentialResultNotFoundException;
use Iam\Authentication\Domain\ApiKeyCredential\ApiKeyCredential;
use Iam\Tests\Authentication\Support\Builder\ApiKeyCredentialBuilder;
use Iam\Tests\Authentication\Support\Double\FakeApiKeyHasher;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Tests\Support\TestCase\AbstractIterableFinderTestCase;
use Shared\Tests\Support\TestCase\IdTiebreakerTrait;
use Symfony\Component\Clock\Clock;

/**
 * @extends AbstractIterableFinderTestCase<ApiKeyCredentialResult>
 */
final class DbalApiKeyCredentialFinderTest extends AbstractIterableFinderTestCase
{
    use IdTiebreakerTrait;

    #[Test]
    public function itGetsByKeyId(): void
    {
        // Given
        $hasher = new FakeApiKeyHasher();
        $other = ApiKeyCredentialBuilder::new()->withHasher($hasher)->create();

        $builder = ApiKeyCredentialBuilder::new()->withHasher($hasher);
        $credential = $builder->create();
        $this->store($other, $credential);

        // When
        $result = $this->finder()->ofKeyId($builder['keyId']->value);

        // Then
        self::assertSame($credential->id->toString(), $result->id);
        self::assertSame($builder['identityId'], $result->identityId);
        self::assertSame($builder['label']->value, $result->label);
        self::assertSame($builder['keyId']->value, $result->keyId);
        self::assertFalse($result->revoked);
        self::assertSame(
            $builder['issuedAt']->format(\DateTimeInterface::ATOM),
            $result->issuedAt->format(\DateTimeInterface::ATOM),
        );
        self::assertNull($result->revokedAt);
        self::assertTrue($result->identityAuthenticatable);

        self::assertSame($hasher->hash($builder['secret']), $result->secretHash);
    }

    #[Test]
    public function itThrowsWhenKeyIdNotFound(): void
    {
        // Then
        $this->expectException(ApiKeyCredentialResultNotFoundException::class);

        // When
        $this->finder()->ofKeyId(ApiKeyCredentialBuilder::sample('keyId')->value);
    }

    #[Test]
    public function itFiltersByIdentity(): void
    {
        // Given
        $hasher = new FakeApiKeyHasher();
        $other = ApiKeyCredentialBuilder::new()->withHasher($hasher)->create();

        $identityId = ApiKeyCredentialBuilder::sample('identityId');
        $credential = ApiKeyCredentialBuilder::new()->withIdentityId($identityId)->withHasher($hasher)->create();
        $this->store($other, $credential);

        // When
        $results = iterator_to_array($this->finder()->byIdentity($identityId), false);

        // Then
        self::assertCount(1, $results);
        self::assertSame($credential->id->toString(), $results[0]->id);
    }

    protected function finder(): ApiKeyCredentialFinderInterface
    {
        return $this->service(ApiKeyCredentialFinderInterface::class);
    }

    /**
     * @return list<string>
     */
    protected function seed(int $count): array
    {
        $credentials = ApiKeyCredentialBuilder::new()->withHasher(new FakeApiKeyHasher())->many($count)->create();
        $this->store(...$credentials);

        return array_map(static fn (ApiKeyCredential $credential): string => $credential->id->toString(), $credentials);
    }

    protected function indexOf(object $result): string
    {
        return $result->id;
    }

    /**
     * @return array{string, string}
     */
    protected function seedTie(): array
    {
        $ids = [Uuid::uuid7()->toString(), Uuid::uuid7()->toString()];
        sort($ids);
        [$firstId, $secondId] = $ids;

        $hasher = new FakeApiKeyHasher();
        $tiedAt = Clock::get()->now();
        $second = ApiKeyCredentialBuilder::new()->withId($secondId)->withHasher($hasher)->withIssuedAt($tiedAt)->create();
        $first = ApiKeyCredentialBuilder::new()->withId($firstId)->withHasher($hasher)->withIssuedAt($tiedAt)->create();
        $this->store($second, $first);

        return [$firstId, $secondId];
    }

    /**
     * @return array{string, string}
     */
    protected function seedConflictingOrder(): array
    {
        $now = Clock::get()->now();
        $smallerId = Uuid::uuid7($now)->toString();
        $largerId = Uuid::uuid7($now->modify('+1 hour'))->toString();

        $hasher = new FakeApiKeyHasher();
        $first = ApiKeyCredentialBuilder::new()->withId($largerId)->withHasher($hasher)->withIssuedAt($now)->create();
        $second = ApiKeyCredentialBuilder::new()->withId($smallerId)->withHasher($hasher)->withIssuedAt($now->modify('+1 hour'))->create();
        $this->store($first, $second);

        return [$largerId, $smallerId];
    }
}
