<?php

declare(strict_types=1);

namespace Shared\Infrastructure\VerificationCode;

use Shared\Application\VerificationCode\VerificationCodeStoreInterface;
use Shared\Domain\Exception\VerificationCodeAttemptsExceededException;
use Shared\Domain\Exception\VerificationCodeNotFoundException;
use Shared\Domain\Service\CodeChallengerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Webmozart\Assert\Assert;

final readonly class NativeCodeChallenger implements CodeChallengerInterface
{
    public function __construct(
        private VerificationCodeStoreInterface $store,
        #[Autowire('%env(CODE_CHALLENGER_HASH_SECRET)%')]
        #[\SensitiveParameter]
        private string $secret,
        #[Autowire(param: 'code_challenger.max_attempts')]
        private int $maxAttempts,
        #[Autowire(param: 'code_challenger.expiry')]
        private string $expiry,
    ) {
        Assert::positiveInteger($this->maxAttempts);
        Assert::stringNotEmpty($this->expiry);
    }

    public function issue(\BackedEnum $purpose, string $subjectId, \DateTimeImmutable $now): string
    {
        $code = \sprintf('%06d', random_int(0, 999999));

        $this->store->save($purpose, $subjectId, $this->hash($code), $now->modify($this->expiry));

        return $code;
    }

    public function verify(\BackedEnum $purpose, string $subjectId, #[\SensitiveParameter] string $code, \DateTimeImmutable $now): bool
    {
        $record = $this->store->find($purpose, $subjectId);

        if (null === $record) {
            throw VerificationCodeNotFoundException::forSubject($purpose, $subjectId);
        }

        if ($record->expiresAt < $now) {
            $this->store->delete($purpose, $subjectId);

            throw VerificationCodeNotFoundException::forSubject($purpose, $subjectId);
        }

        if ($record->attempts >= $this->maxAttempts) {
            throw VerificationCodeAttemptsExceededException::forSubject($purpose, $subjectId);
        }

        if (!hash_equals($record->codeHash, $this->hash($code))) {
            $this->store->incrementAttempts($purpose, $subjectId);

            return false;
        }

        $this->store->delete($purpose, $subjectId);

        return true;
    }

    private function hash(#[\SensitiveParameter] string $code): string
    {
        return hash_hmac('sha256', $code, $this->secret);
    }
}
