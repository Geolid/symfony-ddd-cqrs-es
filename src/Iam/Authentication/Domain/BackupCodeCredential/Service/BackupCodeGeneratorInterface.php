<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\BackupCodeCredential\Service;

interface BackupCodeGeneratorInterface
{
    public const int DIGITS = 8;

    /**
     * @return list<non-empty-string>
     */
    public function generate(int $count): array;
}
