<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Support\Factory;

use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Authentication\Domain\TotpCredential\TotpCredential;
use Iam\Authentication\Domain\TotpCredential\ValueObject\TotpCredentialId;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Ramsey\Uuid\Uuid;
use Support\Foundry\AbstractAggregateFactory;
use Symfony\Component\Clock\Clock;
use Webmozart\Assert\Assert;

use function Zenstruck\Foundry\faker;

/**
 * @phpstan-type Inputs = array{
 *     id: TotpCredentialId,
 *     identityId: string,
 *     secret: string,
 *     cipher: TotpCipherInterface,
 *     enrolledAt: \DateTimeImmutable,
 *     unenrolledAt: \DateTimeImmutable,
 * }
 *
 * @extends AbstractAggregateFactory<TotpCredential, Inputs>
 */
final class TotpCredentialFactory extends AbstractAggregateFactory
{
    public static function class(): string
    {
        return TotpCredential::class;
    }

    public function withId(string $id): self
    {
        return $this->with(['id' => TotpCredentialId::fromString($id)]);
    }

    public function withIdentityId(string $identityId): self
    {
        return $this->with(['identityId' => $identityId]);
    }

    public function withSecret(string $secret): self
    {
        return $this->with(['secret' => $secret]);
    }

    public function withCipher(TotpCipherInterface $cipher): self
    {
        return $this->with(['cipher' => $cipher]);
    }

    public function withEnrolledAt(\DateTimeImmutable $enrolledAt): self
    {
        return $this->with(['enrolledAt' => $enrolledAt]);
    }

    public function unenrolled(?\DateTimeImmutable $unenrolledAt = null): self
    {
        return $this->with(array_filter(['unenrolledAt' => $unenrolledAt]))->transition(
            static function (TotpCredential $credential, array $inputs): void {
                $credential->unenroll($inputs['identityId'], $inputs['unenrolledAt']);
            },
        );
    }

    protected static function build(array $parameters): AggregateRoot
    {
        Assert::isInstanceOf($cipher = $parameters['cipher'] ?? null, TotpCipherInterface::class);

        return TotpCredential::enroll(
            id: $parameters['id'],
            identityId: $parameters['identityId'],
            secret: $parameters['secret'],
            cipher: $cipher,
            enrolledAt: $parameters['enrolledAt'],
        );
    }

    protected function defaults(): array
    {
        $now = Clock::get()->now();

        return [
            'id' => TotpCredentialIdFactory::new(),
            'identityId' => Uuid::uuid7()->toString(),
            'secret' => faker()->totpSecret(),
            'enrolledAt' => $now,
            'unenrolledAt' => $now->modify('+1 day'),
        ];
    }
}
