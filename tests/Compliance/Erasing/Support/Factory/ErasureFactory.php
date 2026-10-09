<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasing\Support\Factory;

use Compliance\Erasing\Domain\Erasure;
use Compliance\Erasing\Domain\Specification\ErasureRetentionExpiredSpecification;
use Compliance\Erasing\Domain\ValueObject\ErasureId;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Ramsey\Uuid\Uuid;
use Support\Foundry\AbstractAggregateFactory;
use Symfony\Component\Clock\Clock;

/**
 * @phpstan-type Inputs = array{
 *     id: ErasureId,
 *     identityId: string,
 *     requestedAt: \DateTimeImmutable,
 *     cancelledAt: \DateTimeImmutable,
 *     approvedAt: \DateTimeImmutable,
 * }
 *
 * @extends AbstractAggregateFactory<Erasure, Inputs>
 */
final class ErasureFactory extends AbstractAggregateFactory
{
    public static function class(): string
    {
        return Erasure::class;
    }

    public function withId(string $id): self
    {
        return $this->with(['id' => ErasureId::fromString($id)]);
    }

    public function withIdentityId(string $identityId): self
    {
        return $this->with(['identityId' => $identityId]);
    }

    public function withRequestedAt(\DateTimeImmutable $requestedAt): self
    {
        return $this->with(['requestedAt' => $requestedAt]);
    }

    public function cancelled(?\DateTimeImmutable $cancelledAt = null): self
    {
        return $this->with(array_filter(['cancelledAt' => $cancelledAt]))->transition(
            static function (Erasure $erasure, array $inputs): void {
                $erasure->cancel($inputs['cancelledAt']);
            },
        );
    }

    public function approved(?\DateTimeImmutable $approvedAt = null): self
    {
        return $this->with(array_filter(['approvedAt' => $approvedAt]))->transition(
            static function (Erasure $erasure, array $inputs): void {
                $erasure->approve($inputs['approvedAt']);
            },
        );
    }

    protected static function build(array $parameters): AggregateRoot
    {
        return Erasure::request(
            id: $parameters['id'],
            identityId: $parameters['identityId'],
            requestedAt: $parameters['requestedAt'],
        );
    }

    protected function defaults(): array
    {
        $now = Clock::get()->now();

        return [
            'id' => ErasureIdFactory::new(),
            'identityId' => Uuid::uuid7()->toString(),
            'requestedAt' => $now,
            'cancelledAt' => $now->modify('+1 hour'),
            'approvedAt' => $now->modify(\sprintf('+%d days', ErasureRetentionExpiredSpecification::DAYS + 1)),
        ];
    }
}
