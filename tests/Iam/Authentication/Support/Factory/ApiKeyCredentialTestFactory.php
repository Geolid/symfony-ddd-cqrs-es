<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Support\Factory;

use Iam\Authentication\Domain\ApiKeyCredential\ApiKeyCredential;
use Iam\Authentication\Domain\ApiKeyCredential\Service\ApiKeyHasherInterface;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\ApiKeyCredentialId;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\KeyId;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\Label;
use Support\ClockSequence;
use Support\Factory\AbstractAggregateTestFactory;
use Support\SeededFaker;
use Symfony\Component\Clock\Clock;
use Webmozart\Assert\Assert;

/**
 * @phpstan-type Attributes = array{
 *     id: string,
 *     identityId?: string,
 *     label: string,
 *     keyId: string,
 *     secret: string,
 *     issuedAt: \DateTimeInterface,
 *     hasher?: ApiKeyHasherInterface,
 * }
 *
 * @extends AbstractAggregateTestFactory<ApiKeyCredential, Attributes>
 */
final class ApiKeyCredentialTestFactory extends AbstractAggregateTestFactory
{
    public function withId(string $id): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['id' => $id]));
    }

    public function withIdentityId(string $identityId): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['identityId' => $identityId]));
    }

    public function withLabel(string $label): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['label' => $label]));
    }

    public function withKeyId(string $keyId): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['keyId' => $keyId]));
    }

    public function withSecret(string $secret): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['secret' => $secret]));
    }

    public function withIssuedAt(\DateTimeImmutable $issuedAt): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['issuedAt' => $issuedAt]));
    }

    public function withHasher(ApiKeyHasherInterface $hasher): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['hasher' => $hasher]));
    }

    public function revoked(?string $identityId = null, ?\DateTimeImmutable $revokedAt = null): self
    {
        $identityId ??= $this->attributes['identityId'] ?? Uuid::uuid7()->toString();
        \assert(\is_string($identityId));
        $revokedAt ??= Clock::get()->now();

        return $this->withAttributes(array_merge($this->attributes, ['identityId' => $identityId]))
            ->withModifier(static fn (ApiKeyCredential $credential) => $credential->revoke($identityId, $revokedAt));
    }

    protected function defaults(): array
    {
        Assert::string($label = SeededFaker::get()->words(2, true));

        return [
            'id' => ApiKeyCredentialId::generate()->toString(),
            'identityId' => Uuid::uuid7()->toString(),
            'label' => $label,
            'keyId' => KeyId::PREFIX.bin2hex(random_bytes(8)),
            'secret' => bin2hex(random_bytes(32)),
            'issuedAt' => ClockSequence::next(),
        ];
    }

    protected function build(array $attributes): ApiKeyCredential
    {
        Assert::stringNotEmpty($id = $attributes['id']);
        Assert::stringNotEmpty($identityId = $attributes['identityId'] ?? null);
        Assert::stringNotEmpty($label = $attributes['label']);
        Assert::stringNotEmpty($keyId = $attributes['keyId']);
        Assert::stringNotEmpty($secret = $attributes['secret']);
        Assert::isInstanceOf($hasher = $attributes['hasher'] ?? null, ApiKeyHasherInterface::class);
        Assert::isInstanceOf($issuedAt = $attributes['issuedAt'], \DateTimeInterface::class);

        return ApiKeyCredential::issue(
            ApiKeyCredentialId::fromString($id),
            $identityId,
            Label::fromString($label),
            KeyId::fromString($keyId),
            $secret,
            $hasher,
            \DateTimeImmutable::createFromInterface($issuedAt),
        );
    }
}
