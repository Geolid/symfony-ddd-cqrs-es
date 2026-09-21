<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\TotpCredential\Service;

interface TotpBackupCodeGeneratorInterface
{
    public const int DIGITS = 8;

    /**
     * @return list<non-empty-string>
     */
    public function generate(int $count): array;
}
