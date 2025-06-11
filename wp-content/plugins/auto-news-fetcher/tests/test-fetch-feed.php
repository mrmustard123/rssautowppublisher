<?php
/**
 * Test unitario para Auto News Fetcher (Paso 4.1)
 */
 
/**
 * Test unitario para Auto News Fetcher (Paso 4.1)
 * 
 * EJECUTAR DESDE LA RAÍZ DE WORDPRESS CON:
 * ./vendor/bin/phpunit wp-content/plugins/auto-news-fetcher/tests/test-fetch-feed.php
 */
//require_once dirname(__DIR__, 4) . '/wp-load.php'; // Carga WordPres4
require_once dirname(__DIR__) . '/auto-news-fetcher.php'; // Carga tu plugin
// Carga WordPress - RUTA ABSOLUTA CORRECTA
require_once 'C:\wamp64\www\wp_simulation\wp-load.php';

// Carga tu plugin
require_once dirname(__DIR__) . '/auto-news-fetcher.php';

class ANF_FetchFeedTest extends WP_UnitTestCase {
    // Configuración inicial
    public function setUp(): void {
        parent::setUp();
    }

    public function test_fetch_valid_feed() {
        // Mock para fetch_feed
        add_filter('pre_http_request', function() {
            return [
                'body' => '<?xml version="1.0"?>
                <rss><channel>
                    <title>Test Feed</title>
                    <item><title>Noticia 1</title><description>Contenido</description><link>https://ejemplo.com/1</link></item>
                </channel></rss>',
                'response' => ['code' => 200]
            ];
        });

        $feed = fetch_feed('https://feed-valido.example.com');
        $this->assertNotInstanceOf('WP_Error', $feed);
        $this->assertCount(1, $feed->get_items());
    }
}