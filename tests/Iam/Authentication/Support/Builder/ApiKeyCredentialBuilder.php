<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Support\Builder;

use Iam\Authentication\Domain\ApiKeyCredential\ApiKeyCredential;
use Iam\Authentication\Domain\ApiKeyCredential\Service\ApiKeyHasherInterface;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\ApiKeyCredentialId;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\KeyId;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\Label;
use Support\Builder\AbstractAggregateBuilder;
use Support\SeededFaker;
use Symfony\Component\Clock\Clock;
use Webmozart\Assert\Assert;

/**
 * @phpstan-type Attributes = array{
 *     id: ApiKeyCredentialId,
 *     identityId: string,
 *     label: Label,
 *     keyId: KeyId,
 *     secret: string,
 *     issuedAt: \DateTimeImmutable,
 *     revokedAt: \DateTimeImmutable,
 *     hasher?: ApiKeyHasherInterface,
 * }
 *
 * @extends AbstractAggregateBuilder<ApiKeyCredential, Attributes>
 */
final class ApiKeyCredentialBuilder extends AbstractAggregateBuilder
{
    public function withId(string $id): self
    {
        return $this->withAttributes(id: ApiKeyCredentialId::fromString($id));
    }

    public function withIdentityId(string $identityId): self
    {
        return $this->withAttributes(identityId: $identityId);
    }

    public function withLabel(string $label): self
    {
        return $this->withAttributes(label: Label::fromString($label));
    }

    public function withKeyId(string $keyId): self
    {
        return $this->withAttributes(keyId: KeyId::fromString($keyId));
    }

    public function withSecret(string $secret): self
    {
        return $this->withAttributes(secret: $secret);
    }

    public function withIssuedAt(\DateTimeImmutable $issuedAt): self
    {
        return $this->withAttributes(issuedAt: $issuedAt);
    }

    public function withHasher(ApiKeyHasherInterface $hasher): self
    {
        return $this->withAttributes(hasher: $hasher);
    }

    public function revoked(?\DateTimeImmutable $revokedAt = null): self
    {
        $builder = null !== $revokedAt ? $this->withAttributes(revokedAt: $revokedAt) : $this;

        return $builder->withModifier(static function (ApiKeyCredential $credential, self $builder): void {
            $credential->revoke($builder['identityId'], $builder['revokedAt']);
        });
    }

    protected static function defaults(): array
    {
        $now = Clock::get()->now();

        return [
            'id' => ApiKeyCredentialId::generate(...),
            'identityId' => static fn (): string => Uuid::uuid7()->toString(),
            'label' => static function (): Label {
                Assert::string($label = SeededFaker::get()->unique()->words(2, true));

                return Label::fromString($label);
            },
            'keyId' => static fn (): KeyId => KeyId::fromString(KeyId::PREFIX.bin2hex(random_bytes(8))),
            'secret' => static fn (): string => bin2hex(random_bytes(32)),
            'issuedAt' => static fn (): \DateTimeImmutable => $now,
            'revokedAt' => static fn (): \DateTimeImmutable => $now->modify('+1 day'),
        ];
    }

    protected function build(): ApiKeyCredential
    {
        return ApiKeyCredential::issue(
            id: $this['id'],
            identityId: $this['identityId'],
            label: $this['label'],
            keyId: $this['keyId'],
            secret: $this['secret'],
            hasher: $this->hasher(),
            issuedAt: $this['issuedAt'],
        );
    }

    private function hasher(): ApiKeyHasherInterface
    {
        Assert::isInstanceOf($hasher = $this['hasher'], ApiKeyHasherInterface::class);

        return $hasher;
    }
}
