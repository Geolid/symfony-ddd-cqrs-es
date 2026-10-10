<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Support\Factory;

use Iam\Authentication\Domain\ApiKeyCredential\ApiKeyCredential;
use Iam\Authentication\Domain\ApiKeyCredential\Service\ApiKeyHasherInterface;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\ApiKeyCredentialId;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\KeyId;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\Label;
use Shared\Tests\Support\Factory\LabelFactory;
use Support\Foundry\AbstractAggregateFactory;
use Symfony\Component\Clock\Clock;
use Webmozart\Assert\Assert;

use function Zenstruck\Foundry\faker;

/**
 * @phpstan-type Inputs = array{
 *     id: ApiKeyCredentialId,
 *     identityId: string,
 *     label: Label,
 *     keyId: KeyId,
 *     secret: string,
 *     hasher: ApiKeyHasherInterface,
 *     issuedAt: \DateTimeImmutable,
 *     revokedAt: \DateTimeImmutable,
 * }
 *
 * @extends AbstractAggregateFactory<ApiKeyCredential, Inputs>
 */
final class ApiKeyCredentialFactory extends AbstractAggregateFactory
{
    public static function class(): string
    {
        return ApiKeyCredential::class;
    }

    public function withId(ApiKeyCredentialId $id): self
    {
        return $this->with(['id' => $id]);
    }

    public function withIdentityId(string $identityId): self
    {
        return $this->with(['identityId' => $identityId]);
    }

    public function withLabel(Label $label): self
    {
        return $this->with(['label' => $label]);
    }

    public function withKeyId(KeyId $keyId): self
    {
        return $this->with(['keyId' => $keyId]);
    }

    public function withSecret(string $secret): self
    {
        return $this->with(['secret' => $secret]);
    }

    public function withHasher(ApiKeyHasherInterface $hasher): self
    {
        return $this->with(['hasher' => $hasher]);
    }

    public function withIssuedAt(\DateTimeImmutable $issuedAt): self
    {
        return $this->with(['issuedAt' => $issuedAt]);
    }

    public function revoked(?\DateTimeImmutable $revokedAt = null): self
    {
        return $this->with(array_filter(['revokedAt' => $revokedAt]))->transition(
            static function (ApiKeyCredential $credential, array $inputs): void {
                $credential->revoke($inputs['identityId'], $inputs['revokedAt']);
            },
        );
    }

    protected static function build(array $parameters): AggregateRoot
    {
        Assert::isInstanceOf($hasher = $parameters['hasher'] ?? null, ApiKeyHasherInterface::class);

        return ApiKeyCredential::issue(
            id: $parameters['id'],
            identityId: $parameters['identityId'],
            label: $parameters['label'],
            keyId: $parameters['keyId'],
            secret: $parameters['secret'],
            hasher: $hasher,
            issuedAt: $parameters['issuedAt'],
        );
    }

    protected function defaults(): array
    {
        $now = Clock::get()->now();

        return [
            'id' => ApiKeyCredentialIdFactory::new(),
            'identityId' => Uuid::uuid7()->toString(),
            'label' => LabelFactory::new(),
            'keyId' => KeyIdFactory::new(),
            'secret' => faker()->apiKeySecret(),
            'issuedAt' => $now,
            'revokedAt' => $now->modify('+1 day'),
        ];
    }
}
