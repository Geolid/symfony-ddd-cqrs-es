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
 *     id: ApiKeyCredentialId,
 *     identityId: string,
 *     label: Label,
 *     keyId: KeyId,
 *     secret: string,
 *     issuedAt: \DateTimeImmutable,
 *     hasher?: ApiKeyHasherInterface,
 * }
 *
 * @extends AbstractAggregateTestFactory<ApiKeyCredential, Attributes>
 */
final class ApiKeyCredentialTestFactory extends AbstractAggregateTestFactory
{
    public function withId(string $id): self
    {
        return $this->withAttributes(['id' => ApiKeyCredentialId::fromString($id)]);
    }

    public function withIdentityId(string $identityId): self
    {
        return $this->withAttributes(['identityId' => $identityId]);
    }

    public function withLabel(string $label): self
    {
        return $this->withAttributes(['label' => Label::fromString($label)]);
    }

    public function withKeyId(string $keyId): self
    {
        return $this->withAttributes(['keyId' => KeyId::fromString($keyId)]);
    }

    public function withSecret(string $secret): self
    {
        return $this->withAttributes(['secret' => $secret]);
    }

    public function withIssuedAt(\DateTimeImmutable $issuedAt): self
    {
        return $this->withAttributes(['issuedAt' => $issuedAt]);
    }

    public function withHasher(ApiKeyHasherInterface $hasher): self
    {
        return $this->withAttributes(['hasher' => $hasher]);
    }

    public function revoked(?\DateTimeImmutable $revokedAt = null): self
    {
        $revokedAt ??= Clock::get()->now();

        return $this->withModifier(static function (ApiKeyCredential $credential, array $attributes) use ($revokedAt): void {
            $credential->revoke($attributes['identityId'], $revokedAt);
        });
    }

    protected function defaults(): array
    {
        Assert::string($label = SeededFaker::get()->words(2, true));

        return [
            'id' => ApiKeyCredentialId::generate(),
            'identityId' => Uuid::uuid7()->toString(),
            'label' => Label::fromString($label),
            'keyId' => KeyId::fromString(KeyId::PREFIX.bin2hex(random_bytes(8))),
            'secret' => bin2hex(random_bytes(32)),
            'issuedAt' => ClockSequence::next(),
        ];
    }

    protected function build(): ApiKeyCredential
    {
        Assert::isInstanceOf($hasher = $this->attribute('hasher'), ApiKeyHasherInterface::class);

        return ApiKeyCredential::issue(
            id: $this->attribute('id'),
            identityId: $this->attribute('identityId'),
            label: $this->attribute('label'),
            keyId: $this->attribute('keyId'),
            secret: $this->attribute('secret'),
            hasher: $hasher,
            issuedAt: $this->attribute('issuedAt'),
        );
    }
}
