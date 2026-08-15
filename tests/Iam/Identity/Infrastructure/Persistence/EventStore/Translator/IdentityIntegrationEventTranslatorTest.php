<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Persistence\EventStore\Translator;

use Iam\Identity\Application\Event\IdentityErasedIntegrationEvent;
use Iam\Identity\Application\Event\IdentityReactivatedIntegrationEvent;
use Iam\Identity\Application\Event\IdentitySuspendedIntegrationEvent;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Shared\Infrastructure\Persistence\EventStore\IntegrationStreamId;
use Support\AbstractIntegrationTestCase;

final class IdentityIntegrationEventTranslatorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishesTheErasureOnIdentityErased(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->erased()->create();

        // When
        $this->store($identity);

        // Then
        $published = $this->publishedTo(IntegrationStreamId::build('iam.identity', $identity->id()->toString()));
        self::assertCount(1, $published);
        $event = $published[0];
        self::assertInstanceOf(IdentityErasedIntegrationEvent::class, $event);
        self::assertSame($identity->id()->toString(), $event->identityId);
    }

    #[Test]
    public function itPublishesTheSuspensionOnIdentitySuspended(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->suspended()->create();

        // When
        $this->store($identity);

        // Then
        $published = $this->publishedTo(IntegrationStreamId::build('iam.identity', $identity->id()->toString()));
        self::assertCount(1, $published);
        $event = $published[0];
        self::assertInstanceOf(IdentitySuspendedIntegrationEvent::class, $event);
        self::assertSame($identity->id()->toString(), $event->identityId);
    }

    #[Test]
    public function itPublishesTheReactivationOnIdentityReactivated(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->suspended()->reactivated()->create();

        // When
        $this->store($identity);

        // Then
        $published = $this->publishedTo(IntegrationStreamId::build('iam.identity', $identity->id()->toString()));
        self::assertCount(2, $published);
        $event = $published[1];
        self::assertInstanceOf(IdentityReactivatedIntegrationEvent::class, $event);
        self::assertSame($identity->id()->toString(), $event->identityId);
    }
}
