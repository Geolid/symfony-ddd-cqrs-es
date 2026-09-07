<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasure\Application\IntegrationEvent\SubjectErasureRequested;

use Compliance\Erasure\Application\IntegrationEvent\SubjectErasureRequested\SubjectErasureRequestedIntegrationEvent;
use Compliance\Tests\Erasure\Support\Builder\SubjectBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class SubjectErasureRequestedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = SubjectBuilder::new()->erasureRequested();
        $subject = $builder->create();

        // When
        $this->store($subject);

        // Then
        $event = $this->publishedEventOf(SubjectErasureRequestedIntegrationEvent::class);
        self::assertSame($subject->id->toString(), $event->subjectId);
        self::assertSame(
            $builder['requestedAt']->format(\DateTimeInterface::ATOM),
            $event->requestedAt->format(\DateTimeInterface::ATOM),
        );
    }
}
