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
    $feeds = explode("\n", $options['feeds']);
    $include_keywords = array_filter(array_map('trim', explode(',', $options['include_keywords'] ?? '')));
    $exclude_keywords = array_filter(array_map('trim', explode(',', $options['exclude_keywords'] ?? '')));

    foreach ($feeds as $feed_url) {
        $feed_url = trim($feed_url);
        if (empty($feed_url)) continue;

        $rss = @simplexml_load_file($feed_url);
        if (!$rss) continue;

        foreach ($rss->channel->item as $item) {
            $title = (string) $item->title;
            $link = (string) $item->link;
            $description = (string) $item->description;

            // Curación por palabras clave
            $content = $title . ' ' . $description;
            $passes_include = empty($include_keywords) || preg_match('/\b(' . implode('|', $include_keywords) . ')\b/i', $content);
            $passes_exclude = !preg_match('/\b(' . implode('|', $exclude_keywords) . ')\b/i', $content);

            if ($passes_include && $passes_exclude) {
                // Verificar si ya existe el post
                if (!get_page_by_title($title, OBJECT, 'post')) {
                    wp_insert_post([
                        'post_title'   => $title,
                        'post_content' => $description . "\n\nFuente: <a href=\"$link\">$link</a>",
                        'post_status'  => 'draft',
                        'post_type'    => 'post',
                    ]);
                }
            }
        }
    }

    echo '<div class="updated"><p>Importación completada.</p></div>';
}
