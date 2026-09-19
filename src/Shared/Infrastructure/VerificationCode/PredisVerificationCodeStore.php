<?php

declare(strict_types=1);

namespace Shared\Infrastructure\VerificationCode;

use Patchlevel\Hydrator\Hydrator;
use Predis\Client;
use Predis\Transaction\MultiExec;
use Psr\Clock\ClockInterface;
use Shared\Application\VerificationCode\VerificationCodeRecord;
use Shared\Application\VerificationCode\VerificationCodeStoreInterface;
use Shared\Domain\ValueObject\VerificationCodeKey;
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

    public function save(VerificationCodeKey $key, string $codeHash, \DateTimeImmutable $expiresAt): void
    {
        $prefixedKey = $this->prefixKey($key);
        $ttl = $expiresAt->getTimestamp() - $this->clock->now()->getTimestamp();
        $record = $this->hydrator->extract(new VerificationCodeRecord($codeHash, $expiresAt, 0));

        $this->client->transaction(static function (MultiExec $tx) use ($prefixedKey, $record, $ttl): void {
            $tx->hmset($prefixedKey, $record);
            $tx->expire($prefixedKey, max($ttl, 1));
        });
    }

    public function find(VerificationCodeKey $key): ?VerificationCodeRecord
    {
        $raw = $this->client->hgetall($this->prefixKey($key));

        if ([] === $raw) {
            return null;
        }

        return $this->hydrator->hydrate(VerificationCodeRecord::class, $raw);
    }

    public function incrementAttempts(VerificationCodeKey $key): void
    {
        $this->client->eval(
            <<<'LUA'
            if redis.call('EXISTS', KEYS[1]) == 1 then
                redis.call('HINCRBY', KEYS[1], ARGV[1], 1)
            end
            LUA,
            1,
            $this->prefixKey($key),
            'attempts',
        );
    }

    public function delete(VerificationCodeKey $key): void
    {
        $this->client->del([$this->prefixKey($key)]);
    }

    private function prefixKey(VerificationCodeKey $key): string
    {
        return $this->keyPrefix.$key->toString();
    }
}
