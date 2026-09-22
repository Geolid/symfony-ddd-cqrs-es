<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\BackupCode;

use Iam\Authentication\Domain\BackupCodeCredential\Service\BackupCodeGeneratorInterface;
use Shared\Domain\Service\NumericCodeGeneratorInterface;

final readonly class NativeBackupCodeGenerator implements BackupCodeGeneratorInterface
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
