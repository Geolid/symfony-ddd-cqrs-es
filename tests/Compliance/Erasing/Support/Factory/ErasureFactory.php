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

use function Zenstruck\Foundry\faker;

/**
 * @phpstan-type Inputs = array{
 *     id: ErasureId,
 *     identityId: string,
 *     requestedAt: \DateTimeImmutable,
 *     retentionSeconds: int,
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

    public function withRetentionSeconds(int $retentionSeconds): self
    {
        return $this->with(['retentionSeconds' => $retentionSeconds]);
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
            retentionCalculator: new ErasureRetentionCalculator($parameters['retentionSeconds']),
        );
    }

    protected function initialize(): static
    {
        return parent::initialize()->beforeInstantiate(static function (array $parameters): array {
            Assert::integer($parameters['retentionSeconds']);
            $parameters['approvedAt'] ??= $parameters['requestedAt']->modify(\sprintf('+%d seconds', $parameters['retentionSeconds'] + 1));

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
            'retentionSeconds' => faker()->numberBetween(60, 3600),
            'cancelledAt' => $now->modify('+1 hour'),
        ];
    }
}
