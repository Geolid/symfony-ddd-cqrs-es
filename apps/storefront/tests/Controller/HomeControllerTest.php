<?php

declare(strict_types=1);

namespace Storefront\Tests\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Support\AbstractStorefrontTestCase;

final class HomeControllerTest extends AbstractStorefrontTestCase
{
    #[Test]
    #[DataProvider('provideLocalizedPath')]
    public function itShows(string $locale, string $path): void
    {
        // Given
        $client = self::browser();

        // When
        $client->request('GET', $path);

        // Then
        self::assertResponseIsSuccessful();
        self::assertSame($locale, $client->getRequest()->getLocale());
        self::assertSelectorExists('[data-testid="home-title"]');
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideLocalizedPath(): iterable
    {
        yield 'en' => ['en', '/home'];
        yield 'fr' => ['fr', '/accueil'];
    }

    /**
     * @param array<string, string> $server
     */
    #[Test]
    #[DataProvider('providePreferredLocale')]
    public function itRedirectsToPreferredLocale(array $server, string $expectedPath): void
    {
        // Given
        $client = self::browser();

        // When
        $client->request('GET', $this->path('storefront_home_root'), server: $server);

        // Then
        self::assertResponseRedirects($expectedPath);
    }

    /**
     * @return iterable<string, array{array<string, string>, string}>
     */
    public static function providePreferredLocale(): iterable
    {
        yield 'default' => [[], '/home'];
        yield 'fr' => [['HTTP_ACCEPT_LANGUAGE' => 'fr'], '/accueil'];
    }
}
