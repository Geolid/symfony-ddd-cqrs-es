<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Domain;

use Iam\Identity\Domain\Event\PasswordChanged;
use Iam\Identity\Domain\Event\PasswordSet;
use Iam\Identity\Domain\IdentityId;
use Iam\Identity\Domain\Password;
use Iam\Identity\Domain\PasswordId;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;

final class PasswordTest extends AggregateRootTestCase
{
    #[Test]
    public function itSetsAPassword(): void
    {
        $id = PasswordId::generate();
        $identityId = IdentityId::generate();
        $setAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given()
            ->when(static fn () => Password::set($id, $identityId, 'hashed-secret', $setAt))
            ->then(new PasswordSet($id->toString(), $identityId->toString(), 'hashed-secret', $setAt->format('c')));
    }

    #[Test]
    public function itChangesAPassword(): void
    {
        $id = PasswordId::generate()->toString();
        $identityId = IdentityId::generate()->toString();
        $setAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $changedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(new PasswordSet($id, $identityId, 'old-hash', $setAt->format('c')))
            ->when(static fn (Password $password) => $password->change('new-hash', $changedAt))
            ->then(new PasswordChanged($id, 'new-hash', $changedAt->format('c')));
    }

    protected function aggregateClass(): string
    {
        return Password::class;
    }
}
