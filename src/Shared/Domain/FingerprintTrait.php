<?php

declare(strict_types=1);

namespace Shared\Domain;

trait FingerprintTrait
{
    public function fingerprint(): string
    {
        return hash('sha256', $this->toString());
    }
}
