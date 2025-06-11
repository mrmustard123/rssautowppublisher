<?php
/**
 * Bootstrap para pruebas PHPUnit en WordPress
 */

// Definir rutas absolutas
define('WP_TESTS_DIR', 'C:/wamp64/www/wp_simulation/tests/phpunit/');
define('WP_ROOT_DIR', 'C:/wamp64/www/wp_simulation/');

// Cargar el framework de pruebas de WordPress
require_once WP_TESTS_DIR . 'includes/functions.php';

// Cargar WordPress
require WP_TESTS_DIR . 'includes/bootstrap.php';

// Cargar tu plugin
require_once dirname(__DIR__) . '/auto-news-fetcher.php';