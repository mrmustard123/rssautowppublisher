<?php

/*
File: auto-news-fetcher
Author: Leonardo G. Tellez Saucedo
Created on: 9 jun. de 2025 15:55:49
Email: leonardo616@gmail.com
*/

/**
 * Plugin Name: Auto News Fetcher
 * Description: Plugin para importar, filtrar y publicar noticias automáticamente desde RSS.
 * Version: 1.0
 * Author: Leonardo Tellez
 */

if (!defined('ABSPATH')) exit; // Seguridad básica
/** CODIGO SOLO PARA INICIALIZAR LA PRIMERA VEZ.**
// Hook para inicializar el plugin
add_action('init', 'anf_initialize_plugin');

function anf_initialize_plugin() {
    // Prueba simple: crear un post personalizado al activar el plugin
    // Aquí irán los futuros módulos de RSS y configuraciones
}
*/


// --- Registrar menú en el admin ---
add_action('admin_menu', 'anf_register_menu');

function anf_register_menu() {
    add_menu_page(
        'Auto News',
        'Auto News',
        'manage_options',
        'auto-news-fetcher',
        'anf_render_settings_page',
        'dashicons-rss',
        25
    );
}

add_action('init', function() {
    register_post_type('auto_news', [
        'public' => true,
        'label'  => 'Noticias Automáticas',
        'supports' => ['title', 'editor', 'thumbnail'],
        'menu_icon' => 'dashicons-rss'
    ]);
});

// --- Renderizar la página del plugin ---
function anf_render_settings_page() {
    // Obtener configuraciones actuales
    $options = get_option('anf_settings', [
        'feeds' => '',
        'include_keywords' => '',
        'exclude_keywords' => '',
    ]);

    // Guardar configuración si se envió el formulario
    if (isset($_POST['anf_save_settings'])) {
        $options['feeds'] = sanitize_textarea_field($_POST['anf_feeds']);
        $options['include_keywords'] = sanitize_text_field($_POST['anf_include_keywords']);
        $options['exclude_keywords'] = sanitize_text_field($_POST['anf_exclude_keywords']);
        update_option('anf_settings', $options);
        echo '<div class="updated"><p>Configuración guardada.</p></div>';
    }

    // Buscar y guardar artículos
    if (isset($_POST['anf_fetch_articles'])) {
        anf_fetch_and_save_articles();
    }

    ?>
    <div class="wrap">
        <h1><span class="dashicons dashicons-rss"></span> Auto News Fetcher</h1>

        <form method="post">
            <h2>Feeds RSS</h2>
            <label for="anf_feeds">URLs de Feeds (una por línea):</label><br>
            <textarea name="anf_feeds" rows="5" cols="60"><?php echo esc_textarea($options['feeds']); ?></textarea><br><br>

            <h2>Criterios de Curación</h2>
            <label for="anf_include_keywords">Palabras clave (incluir):</label><br>
            <input type="text" name="anf_include_keywords" value="<?php echo esc_attr($options['include_keywords']); ?>" size="60"><br><br>

            <label for="anf_exclude_keywords">Palabras clave (excluir):</label><br>
            <input type="text" name="anf_exclude_keywords" value="<?php echo esc_attr($options['exclude_keywords']); ?>" size="60"><br><br>

            <input type="submit" name="anf_save_settings" class="button button-primary" value="Guardar configuración">
        </form>

        <hr>

        <h2>Buscar Artículos Nuevos</h2>
        <form method="post">
            <input type="submit" name="anf_fetch_articles" class="button" value="Buscar y guardar artículos">
        </form>
    </div>
    <?php
}

// --- Función para buscar artículos desde los feeds configurados ---
function anf_fetch_and_save_articles() {
    $options = get_option('anf_settings', []);
    $feeds = array_filter(array_map('trim', explode("\n", $options['feeds'] ?? '')));
    $include_keywords = array_filter(array_map('trim', explode(',', $options['include_keywords'] ?? '')));
    $exclude_keywords = array_filter(array_map('trim', explode(',', $options['exclude_keywords'] ?? '')));

    if (empty($feeds)) {
        error_log("[Auto News Fetcher] No hay feeds configurados.");
        echo '<div class="notice notice-error"><p>Error: No hay feeds RSS configurados.</p></div>';
        return;
    }

    require_once(ABSPATH . WPINC . '/feed.php'); // Cargar la librería de feeds de WP

    $imported_count = 0;
    $errors = [];

    foreach ($feeds as $feed_url) {
        try {
            $feed = fetch_feed($feed_url); // Usa el sistema de caché de WordPress

            if (is_wp_error($feed)) {
                throw new Exception($feed->get_error_message());
            }

            $items = $feed->get_items(0, 10); // Primeros 10 artículos
            foreach ($items as $item) {
                if (anf_should_import_item($item, $include_keywords, $exclude_keywords)) {
                    if (anf_save_news_item($item)) {
                        $imported_count++;
                    }
                }
            }
        } catch (Exception $e) {
            $errors[] = "Feed: $feed_url - Error: " . $e->getMessage();
            error_log("[Auto News Fetcher] " . $e->getMessage());
        }
    }

    // Mostrar resultados
    if (!empty($errors)) {
        echo '<div class="notice notice-warning"><p>Ocurrieron errores: ' . implode('<br>', $errors) . '</p></div>';
    }
    echo '<div class="updated"><p>Importación completada. Artículos nuevos: ' . $imported_count . '</p></div>';
}


// --- Función para evaluar si un artículo debe importarse ---
function anf_should_import_item($item, $include_keywords, $exclude_keywords) {
    $title = $item->get_title();
    $description = $item->get_description();
    $content = $title . ' ' . $description;

    // Filtro por palabras clave
    $passes_include = empty($include_keywords) || preg_match('/\b(' . implode('|', $include_keywords) . ')\b/i', $content);
    $passes_exclude = !preg_match('/\b(' . implode('|', $exclude_keywords) . ')\b/i', $content);

    return $passes_include && $passes_exclude;
}

// --- Función para guardar un artículo como CPT ---
function anf_save_news_item($item) {
    $title = $item->get_title();
    $guid = $item->get_id();

    // Evitar duplicados por GUID o título
    if (get_page_by_title($title, OBJECT, 'auto_news') || anf_post_exists_by_guid($guid)) {
        return false;
    }

    $post_id = wp_insert_post([
        'post_title'   => $title,
        'post_content' => $item->get_description() . "\n\n<strong>Fuente:</strong> <a href=\"" . esc_url($item->get_permalink()) . "\">Enlace original</a>",
        'post_status'  => 'draft', // Publicar como borrador para revisión
        'post_type'    => 'auto_news',
        'meta_input'   => [
            'anf_guid' => $guid, // Guardar GUID para evitar duplicados
            'anf_source' => $item->get_feed()->get_title()
        ]
    ]);

    return !is_wp_error($post_id);
}

// --- Función auxiliar: verificar duplicados por GUID ---
function anf_post_exists_by_guid($guid) {
    global $wpdb;
    return $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $wpdb->posts WHERE guid = %s OR ID IN (SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'anf_guid' AND meta_value = %s)",
        $guid, $guid
    )) > 0;
}


// --- Función para validar URLs de feeds (ahora con feedback) ---
function anf_validate_feed_urls($urls, &$error_count) {
    $valid_urls = [];
    $urls = explode("\n", $urls);
    $error_count = 0;

    foreach ($urls as $url) {
        $url = trim($url);
        if (empty($url)) continue;

        // Validar formato URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            error_log("[Auto News Fetcher] URL inválida: $url");
            $error_count++;
            continue;
        }

        // Test de conexión HTTP
        if (!anf_test_feed_connection($url)) {
            $error_count++;
            continue;
        }

        // Verificar si es un feed RSS válido
        if (!@simplexml_load_file($url)) {
            error_log("[Auto News Fetcher] No es un feed RSS válido: $url");
            $error_count++;
            continue;
        }

        $valid_urls[] = esc_url_raw($url); // Sanitización final
    }

    return implode("\n", $valid_urls);
}


// --- Función para sanitizar palabras clave ---
function anf_sanitize_keywords($keywords) {
    $keywords = explode(',', $keywords);
    $clean_keywords = [];

    foreach ($keywords as $keyword) {
        $keyword = trim($keyword);
        if (empty($keyword)) continue;

        // Eliminar caracteres no alfanuméricos (excepto espacios y guiones)
        $keyword = preg_replace('/[^\p{L}\p{N}\s-]/u', '', $keyword);
        $clean_keywords[] = sanitize_text_field($keyword);
    }

    return implode(',', array_filter($clean_keywords));
}


// --- Función para testear conexión a feeds ---
function anf_test_feed_connection($url) {
    $response = wp_remote_get($url, [
        'timeout' => 10, // 10 segundos de timeout
        'sslverify' => false // Opcional: desactiva verificación SSL en localhost
    ]);

    if (is_wp_error($response)) {
        error_log("[Auto News Fetcher] Error de conexión: " . $response->get_error_message() . " - URL: $url");
        return false;
    }

    $http_code = wp_remote_retrieve_response_code($response);
    if ($http_code != 200) {
        error_log("[Auto News Fetcher] HTTP Code $http_code - URL: $url");
        return false;
    }

    return true;
}

// --- Guardado de configuraciones ---
if (isset($_POST['anf_save_settings'])) {
    $error_count = 0;
    $options['feeds'] = anf_validate_feed_urls($_POST['anf_feeds'], $error_count);
    $options['include_keywords'] = anf_sanitize_keywords($_POST['anf_include_keywords']);
    $options['exclude_keywords'] = anf_sanitize_keywords($_POST['anf_exclude_keywords']);

    if ($error_count > 0) {
        echo '<div class="notice notice-warning"><p>Se guardó la configuración, pero se omitieron ' 
             . $error_count . ' URLs inválidas. Verifica el debug.log para detalles.</p></div>';
    } else {
        echo '<div class="updated"><p>Configuración guardada correctamente.</p></div>';
    }

    update_option('anf_settings', $options);
}
