<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasure\Application\Policy;

use Compliance\Erasure\Application\Command\RegisterSubject\RegisterSubject;
use Compliance\Erasure\Application\Policy\RegisterSubjectOnIdentityRegistered;
use Iam\Identity\Application\IntegrationEvent\IdentityRegistered\IdentityRegisteredIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Command\CommandInterface;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class RegisterSubjectOnIdentityRegisteredTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRegisters(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $registeredAt = Clock::get()->now();

        $dispatched = null;
        $commandBus = $this->createMock(CommandBusInterface::class);
        $this->replace(CommandBusInterface::class, $commandBus);
        $commandBus->expects(self::once())->method('dispatch')
            ->willReturnCallback(static function (CommandInterface $command) use (&$dispatched): void {
                $dispatched = $command;
            });

        // When
        $this->trigger(RegisterSubjectOnIdentityRegistered::class, new IdentityRegisteredIntegrationEvent(
            identityId: $identityId,
            registeredAt: $registeredAt,
        ));

        // Then
        self::assertInstanceOf(RegisterSubject::class, $dispatched);
        self::assertSame($identityId, $dispatched->id);
        self::assertSame($registeredAt, $dispatched->registeredAt);
    }
}
