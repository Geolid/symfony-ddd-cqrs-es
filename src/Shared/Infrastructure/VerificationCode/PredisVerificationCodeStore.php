<?php

declare(strict_types=1);

namespace Shared\Infrastructure\VerificationCode;

use Patchlevel\Hydrator\Hydrator;
use Predis\Client;
use Predis\Transaction\MultiExec;
use Psr\Clock\ClockInterface;
use Shared\Application\VerificationCode\VerificationCodeRecord;
use Shared\Application\VerificationCode\VerificationCodeStoreInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PredisVerificationCodeStore implements VerificationCodeStoreInterface
{
    public function __construct(
        #[Autowire(service: 'shared.valkey.client')]
        private Client $client,
        #[Autowire(service: 'shared.hydration.hydrator')]
        private Hydrator $hydrator,
        #[Autowire(param: 'valkey.key_prefix')]
        private string $keyPrefix,
        private ClockInterface $clock,
    ) {
    }

    public function save(\BackedEnum $purpose, string $subjectId, string $codeHash, \DateTimeImmutable $expiresAt): void
    {
        $key = $this->key($purpose, $subjectId);
        $ttl = $expiresAt->getTimestamp() - $this->clock->now()->getTimestamp();
        $record = $this->hydrator->extract(new VerificationCodeRecord($codeHash, $expiresAt, 0));

        $this->client->transaction(static function (MultiExec $tx) use ($key, $record, $ttl): void {
            $tx->hmset($key, $record);
            $tx->expire($key, max($ttl, 1));
        });
    }

    public function find(\BackedEnum $purpose, string $subjectId): ?VerificationCodeRecord
    {
        $row = $this->client->hgetall($this->key($purpose, $subjectId));

        if ([] === $row) {
            return null;
        }

        return $this->hydrator->hydrate(VerificationCodeRecord::class, $row);
    }

    public function incrementAttempts(\BackedEnum $purpose, string $subjectId): void
    {
        $this->client->eval(
            <<<'LUA'
            if redis.call('EXISTS', KEYS[1]) == 1 then
                redis.call('HINCRBY', KEYS[1], ARGV[1], 1)
            end
            LUA,
            1,
            $this->key($purpose, $subjectId),
            'attempts',
        );
    }

    public function delete(\BackedEnum $purpose, string $subjectId): void
    {
        $this->client->del([$this->key($purpose, $subjectId)]);
    }

    private function key(\BackedEnum $purpose, string $subjectId): string
    {
        return \sprintf('%s%s:%s', $this->keyPrefix, $purpose->value, $subjectId);
    }
}
