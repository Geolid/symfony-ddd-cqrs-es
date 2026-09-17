<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Finder\Identity;

use Iam\Identity\Application\Finder\Identity\Exception\IdentityResultNotFoundException;
use Shared\Application\Finder\PaginatableFinderInterface;

/**
 * @extends PaginatableFinderInterface<IdentityResult>
 */
interface IdentityFinderInterface extends PaginatableFinderInterface
{
    /**
     * @throws IdentityResultNotFoundException
     */
    public function ofId(string $id): IdentityResult;

    /**
     * @throws IdentityResultNotFoundException
     */
    public function ofEmail(string $email): IdentityResult;

    public function pendingBefore(\DateTimeImmutable $cutoff): static;
}
