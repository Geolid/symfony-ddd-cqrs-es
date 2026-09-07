<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasure\Application\IntegrationEvent\SubjectErased;

use Compliance\Erasure\Application\IntegrationEvent\SubjectErased\SubjectErasedIntegrationEvent;
use Compliance\Tests\Erasure\Support\Builder\SubjectBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class SubjectErasedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = SubjectBuilder::new()->erasureRequested()->erased();
        $subject = $builder->create();

        // When
        $this->store($subject);

        // Then
        $event = $this->publishedEventOf(SubjectErasedIntegrationEvent::class);
        self::assertSame($subject->id->toString(), $event->subjectId);
        self::assertSame(
            $builder['erasedAt']->format(\DateTimeInterface::ATOM),
            $event->erasedAt->format(\DateTimeInterface::ATOM),
        );
    }
}
