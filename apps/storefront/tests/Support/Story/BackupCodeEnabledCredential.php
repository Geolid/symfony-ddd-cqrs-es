<?php

declare(strict_types=1);

namespace Storefront\Tests\Support\Story;

final readonly class BackupCodeEnabledCredential
{
    /**
     * @param list<non-empty-string> $plainBackupCodes
     */
    public function __construct(
        public string $email,
        public string $password,
        public array $plainBackupCodes,
    ) {
    }
}
