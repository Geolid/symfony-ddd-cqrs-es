<?php

declare(strict_types=1);

namespace Shared\Infrastructure\VerificationCode;

use Shared\Application\VerificationCode\VerificationCodeStoreInterface;
use Shared\Domain\Exception\VerificationCodeAttemptsExceededException;
use Shared\Domain\Exception\VerificationCodeNotFoundException;
use Shared\Domain\Service\CodeChallengerInterface;
use Shared\Domain\Service\NumericCodeGeneratorInterface;
use Shared\Domain\ValueObject\VerificationCodeKey;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Webmozart\Assert\Assert;

final readonly class NativeCodeChallenger implements CodeChallengerInterface
{
    private const int DIGITS = 6;

    public function __construct(
        private VerificationCodeStoreInterface $store,
        private NumericCodeGeneratorInterface $numericCodeGenerator,
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

    public function issue(VerificationCodeKey $key, \DateTimeImmutable $now): string
    {
        $code = $this->numericCodeGenerator->generate(self::DIGITS);

        $this->store->save($key, $this->hash($code), $now->modify($this->expiry));

        return $code;
    }

    public function verify(VerificationCodeKey $key, #[\SensitiveParameter] string $code, \DateTimeImmutable $now): bool
    {
        $record = $this->store->find($key);

        if (null === $record) {
            throw VerificationCodeNotFoundException::forSubject($key->purpose, $key->subjectId);
        }

        if ($record->expiresAt < $now) {
            throw VerificationCodeNotFoundException::forSubject($key->purpose, $key->subjectId);
        }

        if ($record->attempts >= $this->maxAttempts) {
            throw VerificationCodeAttemptsExceededException::forSubject($key->purpose, $key->subjectId);
        }

        if (!hash_equals($record->codeHash, $this->hash($code))) {
            $this->store->incrementAttempts($key);

            return false;
        }

        $this->store->delete($key);

        return true;
    }

    private function hash(#[\SensitiveParameter] string $code): string
    {
        return hash_hmac('sha256', $code, $this->secret);
    }
}
