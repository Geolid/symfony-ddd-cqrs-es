<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasing\Support\Factory;

use Compliance\Erasing\Domain\Erasure;
use Compliance\Erasing\Domain\Service\ErasureRetentionCalculator;
use Compliance\Erasing\Domain\ValueObject\ErasureId;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Ramsey\Uuid\Uuid;
use Support\Foundry\AbstractAggregateFactory;
use Symfony\Component\Clock\Clock;
use Webmozart\Assert\Assert;

/**
 * @phpstan-type Inputs = array{
 *     id: ErasureId,
 *     identityId: string,
 *     requestedAt: \DateTimeImmutable,
 *     retention: string,
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

    public function withId(ErasureId $id): self
    {
        return $this->with(['id' => $id]);
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
                $erasure->cancel($erasure->identityId, $inputs['cancelledAt']);
            },
        );
    }

    public function withRetention(string $retention): self
    {
        return $this->with(['retention' => $retention]);
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
            retentionCalculator: new ErasureRetentionCalculator(\DateInterval::createFromDateString($parameters['retention'])),
        );
    }

    protected function initialize(): static
    {
        return parent::initialize()->beforeInstantiate(static function (array $parameters): array {
            Assert::isInstanceOf($parameters['requestedAt'], \DateTimeImmutable::class);
            Assert::string($parameters['retention']);
            $parameters['approvedAt'] ??= $parameters['requestedAt']->modify(\sprintf('+%s +1 second', $parameters['retention']));

            return $parameters;
        });
    }

    protected function defaults(): array
    {
        $now = Clock::get()->now();

        return [
            'id' => ErasureIdFactory::new(),
            'identityId' => Uuid::uuid7()->toString(),
            'requestedAt' => $now,
            'retention' => '1 hour',
            'cancelledAt' => $now->modify('+1 hour'),
        ];
    }
}
