<?php

declare(strict_types=1);

namespace Finance\Tests\Payer\Application\Command\RegisterPayer;

use Finance\Payer\Application\Command\RegisterPayer\RegisterPayer;
use Finance\Payer\Application\Finder\Payer\PayerFinderInterface;
use Finance\Payer\Domain\ValueObject\PayerId;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class RegisterPayerHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRegisters(): void
    {
        // Given
        $id = PayerId::generate()->toString();

        // When
        $this->dispatch(new RegisterPayer($id));

        // Then
        $result = $this->service(PayerFinderInterface::class)->ofId($id);
        self::assertSame($id, $result->id);
    }
}
