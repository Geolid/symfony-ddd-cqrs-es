<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Persistence\EventStore\Translator;

use Iam\Identity\Application\Event\IdentityErasedIntegrationEvent;
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
}
