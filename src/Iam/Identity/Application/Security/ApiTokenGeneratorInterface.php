<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Security;

use Shared\Application\Port\AsDrivingPort;

#[AsDrivingPort]
interface ApiTokenGeneratorInterface
{
    public function generate(): GeneratedApiToken;
}
