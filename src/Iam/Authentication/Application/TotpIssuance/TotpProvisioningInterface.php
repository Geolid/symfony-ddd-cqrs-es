<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\TotpIssuance;

use Shared\Application\DrivingPort;

#[DrivingPort]
interface TotpProvisioningInterface
{
    /**
     * @return non-empty-string
     */
    public function generateSecret(): string;

    /**
     * @param non-empty-string $secret
     * @param non-empty-string $label
     * @param non-empty-string $issuer
     */
    public function provisioningUri(#[\SensitiveParameter] string $secret, string $label, string $issuer): string;
}
