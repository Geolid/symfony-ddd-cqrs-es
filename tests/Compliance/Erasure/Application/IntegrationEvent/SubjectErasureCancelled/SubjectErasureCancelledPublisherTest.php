<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasure\Application\IntegrationEvent\SubjectErasureCancelled;

use Compliance\Erasure\Application\IntegrationEvent\SubjectErasureCancelled\SubjectErasureCancelledIntegrationEvent;
use Compliance\Tests\Erasure\Support\Builder\SubjectBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class SubjectErasureCancelledPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = SubjectBuilder::new()->erasureRequested()->erasureCancelled();
        $subject = $builder->create();

        // When
        $this->store($subject);

        // Then
        $event = $this->publishedEventOf(SubjectErasureCancelledIntegrationEvent::class);
        self::assertSame($subject->id->toString(), $event->subjectId);
        self::assertSame(
            $builder['cancelledAt']->format(\DateTimeInterface::ATOM),
            $event->cancelledAt->format(\DateTimeInterface::ATOM),
        );
    }
}
