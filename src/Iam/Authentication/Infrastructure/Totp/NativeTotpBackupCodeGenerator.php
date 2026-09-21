<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\Totp;

use Iam\Authentication\Domain\TotpCredential\Service\TotpBackupCodeGeneratorInterface;
use Shared\Domain\Service\NumericCodeGeneratorInterface;

final readonly class NativeTotpBackupCodeGenerator implements TotpBackupCodeGeneratorInterface
{
    public function __construct(private NumericCodeGeneratorInterface $numericCodeGenerator)
    {
    }

    public function generate(int $count): array
    {
        $codes = [];

        while (\count($codes) < $count) {
            $code = $this->numericCodeGenerator->generate(self::DIGITS);

            if (!\in_array($code, $codes, true)) {
                $codes[] = $code;
            }
        }

        return $codes;
    }
}
