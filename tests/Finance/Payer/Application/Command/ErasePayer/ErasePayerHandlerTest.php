<?php

declare(strict_types=1);

namespace Finance\Tests\Payer\Application\Command\ErasePayer;

use Finance\Payer\Application\Command\ErasePayer\ErasePayer;
use Finance\Payer\Application\Finder\Payer\Exception\PayerResultNotFoundException;
use Finance\Payer\Application\Finder\Payer\PayerFinderInterface;
use Finance\Payer\Domain\Exception\PayerNotFoundException;
use Finance\Tests\Payer\Support\Builder\PayerBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class ErasePayerHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itErases(): void
    {
        // Given
        $payer = PayerBuilder::new()->create();
        $this->store($payer);

        // When
        $this->dispatch(new ErasePayer($payer->id->toString()));

        // Then
        $this->expectException(PayerResultNotFoundException::class);
        $this->service(PayerFinderInterface::class)->ofId($payer->id->toString());
    }

    #[Test]
    public function itIgnoresWhenAlreadyErased(): void
    {
        // Given
        $payer = PayerBuilder::new()->erased()->create();
        $this->store($payer);

        // When
        $this->dispatch(new ErasePayer($payer->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();

        // Then
        $this->expectException(PayerNotFoundException::class);

        // When
        $this->dispatch(new ErasePayer($id));
    }
}
