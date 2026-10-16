<?php

declare(strict_types=1);

namespace Support\Faker;

use Faker\Provider\Base;
use Webmozart\Assert\Assert;

final class CredentialFakerProvider extends Base
{
    /**
     * @param positive-int $length
     *
     * @return non-empty-string
     */
    public function apiKeySecret(int $length = 64): string
    {
        Assert::string($secret = $this->generator->regexify(\sprintf('[a-f0-9]{%d}', $length)));
        Assert::stringNotEmpty($secret);

        return $secret;
    }

    /**
     * @return non-empty-string
     */
    public function totpSecret(): string
    {
        Assert::string($secret = $this->generator->regexify('[A-Z2-7]{32}'));
        Assert::stringNotEmpty($secret);

        return $secret;
    }

    /**
     * @param positive-int $count
     * @param positive-int $length
     *
     * @return list<non-empty-string>
     */
    public function backupCodes(int $count = 2, int $length = 8): array
    {
        $codes = [];

        while (\count($codes) < $count) {
            Assert::string($code = $this->generator->regexify(\sprintf('[0-9]{%d}', $length)));
            Assert::stringNotEmpty($code);
            $codes[$code] = $code;
        }

        return array_values($codes);
    }
}
