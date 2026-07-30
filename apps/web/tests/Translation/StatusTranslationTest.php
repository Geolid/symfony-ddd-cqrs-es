<?php

declare(strict_types=1);

namespace Web\Tests\Translation;

use Ordering\Order\Application\Language\PublishedOrderStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shipping\Shipment\Application\Language\PublishedShipmentStatus;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Web\Tests\Support\AbstractWebTestCase;

final class StatusTranslationTest extends AbstractWebTestCase
{
    private const array LOCALES = ['en', 'fr'];

    #[Test]
    #[DataProvider('provideLocales')]
    public function itTranslatesThePublishedStatusVocabulary(string $locale): void
    {
        // Given
        $translator = $this->serviceAs(TranslatorInterface::class, TranslatorBagInterface::class);

        /** @var array<string, string> $messages */
        $messages = $translator->getCatalogue($locale)->all('messages');

        // When
        $translated = array_values(array_filter(
            array_keys($messages),
            static fn (string $key): bool => str_contains($key, '.status.'),
        ));
        sort($translated);

        // Then
        self::assertSame(self::statusKeys(), $translated);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideLocales(): iterable
    {
        foreach (self::LOCALES as $locale) {
            yield $locale => [$locale];
        }
    }

    /**
     * @return list<string>
     */
    private static function statusKeys(): array
    {
        $keys = [];

        foreach (PublishedOrderStatus::cases() as $status) {
            $keys[] = 'orders.status.'.$status->value;
        }

        foreach (PublishedShipmentStatus::cases() as $status) {
            $keys[] = 'shipments.status.'.$status->value;
        }

        sort($keys);

        return $keys;
    }
}
