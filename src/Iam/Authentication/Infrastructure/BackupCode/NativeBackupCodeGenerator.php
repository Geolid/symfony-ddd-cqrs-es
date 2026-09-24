<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\BackupCode;

use Iam\Authentication\Domain\BackupCodeCredential\Service\BackupCodeGeneratorInterface;
use Shared\Domain\Service\NumericCodeGeneratorInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class NativeBackupCodeGenerator implements BackupCodeGeneratorInterface
{
    public function __construct(
        private NumericCodeGeneratorInterface $numericCodeGenerator,
        #[Autowire(param: 'iam.authentication.backup_code_digit_count')]
        private int $digitCount,
    ) {
    }

    public function generate(int $count): array
    {
        $codes = [];

        while (\count($codes) < $count) {
            $code = $this->numericCodeGenerator->generate($this->digitCount);

            if (!\in_array($code, $codes, true)) {
                $codes[] = $code;
            }
        }

        return $codes;
    }
}
