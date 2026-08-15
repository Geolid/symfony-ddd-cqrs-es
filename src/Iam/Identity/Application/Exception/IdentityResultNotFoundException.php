<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Exception;

use Shared\Application\Exception\ResultNotFoundException;

final class IdentityResultNotFoundException extends ResultNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Identity not found for criteria %s.', json_encode(['id' => $id], \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE)));
    }
}
