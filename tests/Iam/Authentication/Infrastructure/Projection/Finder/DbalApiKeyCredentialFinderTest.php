<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Projection\Finder;

use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialFinderInterface;
use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialResult;
use Iam\Authentication\Application\Finder\ApiKeyCredential\Exception\ApiKeyCredentialResultNotFoundException;
use Iam\Authentication\Domain\ApiKeyCredential\ApiKeyCredential;
use Iam\Tests\Authentication\Support\Double\FakeApiKeyHasher;
use Iam\Tests\Authentication\Support\Factory\ApiKeyCredentialFactory;
use Iam\Tests\Authentication\Support\Factory\KeyIdFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Tests\Support\TestCase\AbstractIterableFinderTestCase;
use Shared\Tests\Support\TestCase\RealColumnLeadsTrait;
use Symfony\Component\Clock\Clock;

use function Zenstruck\Foundry\faker;

/**
 * @extends AbstractIterableFinderTestCase<ApiKeyCredentialResult>
 */
final class DbalApiKeyCredentialFinderTest extends AbstractIterableFinderTestCase
{
    use RealColumnLeadsTrait;

    #[Test]
    public function itGetsByKeyId(): void
    {
        // Given
        $hasher = new FakeApiKeyHasher();
        $other = ApiKeyCredentialFactory::new()->withHasher($hasher)->create();
        $secret = faker()->apiKeySecret();

        $credential = ApiKeyCredentialFactory::new()->withSecret($secret)->withHasher($hasher)->create();
        $this->store($other, $credential);

        // When
        $result = $this->finder()->ofKeyId($credential->keyId->value);

        // Then
        self::assertSame($credential->id->toString(), $result->id);
        self::assertSame($credential->identityId, $result->identityId);
        self::assertSame($credential->label->value, $result->label);
        self::assertSame($credential->keyId->value, $result->keyId);
        self::assertFalse($result->revoked);
        self::assertSame(
            $credential->issuedAt->format(\DateTimeInterface::ATOM),
            $result->issuedAt->format(\DateTimeInterface::ATOM),
        );
        self::assertNull($result->revokedAt);
        self::assertSame($hasher->hash($secret), $result->secretHash);
    }

    #[Test]
    public function itThrowsWhenKeyIdNotFound(): void
    {
        // Then
        $this->expectException(ApiKeyCredentialResultNotFoundException::class);

        // When
        $this->finder()->ofKeyId(KeyIdFactory::new()->create()->value);
    }

    #[Test]
    public function itFiltersByIdentity(): void
    {
        // Given
        $hasher = new FakeApiKeyHasher();
        $other = ApiKeyCredentialFactory::new()->withHasher($hasher)->create();

        $identityId = Uuid::uuid7()->toString();
        $credential = ApiKeyCredentialFactory::new()->withIdentityId($identityId)->withHasher($hasher)->create();
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
        $credentials = ApiKeyCredentialFactory::new()->withHasher(new FakeApiKeyHasher())->many($count)->create();
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
    protected function seedConflictingOrder(): array
    {
        $now = Clock::get()->now();
        $smallerId = Uuid::uuid7($now)->toString();
        $largerId = Uuid::uuid7($now->modify('+1 hour'))->toString();

        $hasher = new FakeApiKeyHasher();
        $first = ApiKeyCredentialFactory::new()->withId($largerId)->withHasher($hasher)->withIssuedAt($now)->create();
        $second = ApiKeyCredentialFactory::new()->withId($smallerId)->withHasher($hasher)->withIssuedAt($now->modify('+1 hour'))->create();
        $this->store($first, $second);

        return [$largerId, $smallerId];
    }
}
