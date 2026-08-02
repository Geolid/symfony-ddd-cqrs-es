<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Domain;

use Iam\Identity\Domain\Event\IdentityRegistered;
use Iam\Identity\Domain\Identity;
use Iam\Identity\Domain\IdentityId;
use Iam\Identity\Domain\Login;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;

final class IdentityTest extends AggregateRootTestCase
{
    #[Test]
    public function itRegistersAnIdentity(): void
    {
        $id = IdentityId::generate();
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given()
            ->when(static fn () => Identity::register($id, Login::fromString('operator@example.com'), $registeredAt))
            ->then(new IdentityRegistered($id->toString(), 'operator@example.com', $registeredAt->format('c')));
    }

    protected function aggregateClass(): string
    {
        return Identity::class;
    }
}
