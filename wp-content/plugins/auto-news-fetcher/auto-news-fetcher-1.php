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

// Agregar opción de menú en el admin
add_action('admin_menu', function () {
    add_menu_page(
        'Auto News Fetcher',
        'Auto News',
        'manage_options',
        'auto-news-fetcher',
        'anf_render_settings_page',
        'dashicons-rss',
        100
    );
});

// Registrar configuración
add_action('admin_init', function () {
    register_setting('anf_options_group', 'anf_settings');

    add_settings_section('anf_feeds_section', 'Feeds RSS', null, 'auto-news-fetcher');
    add_settings_field('feeds', 'URLs de Feeds (una por línea)', 'anf_feeds_field', 'auto-news-fetcher', 'anf_feeds_section');

    add_settings_section('anf_filter_section', 'Criterios de Curación', null, 'auto-news-fetcher');
    add_settings_field('include_keywords', 'Palabras clave (incluir)', 'anf_include_field', 'auto-news-fetcher', 'anf_filter_section');
    add_settings_field('exclude_keywords', 'Palabras clave (excluir)', 'anf_exclude_field', 'auto-news-fetcher', 'anf_filter_section');
});

// Renderizar campos
function anf_feeds_field() {
    $options = get_option('anf_settings');
    echo '<textarea name="anf_settings[feeds]" rows="5" cols="50">' . esc_textarea($options['feeds'] ?? '') . '</textarea>';
}

function anf_include_field() {
    $options = get_option('anf_settings');
    echo '<input type="text" name="anf_settings[include_keywords]" value="' . esc_attr($options['include_keywords'] ?? '') . '" size="50" />';
}

function anf_exclude_field() {
    $options = get_option('anf_settings');
    echo '<input type="text" name="anf_settings[exclude_keywords]" value="' . esc_attr($options['exclude_keywords'] ?? '') . '" size="50" />';
}

// Cargar vista
function anf_render_settings_page() {
    echo '<div class="wrap"><h1>Auto News Fetcher</h1><form method="post" action="options.php">';
    settings_fields('anf_options_group');
    do_settings_sections('auto-news-fetcher');
    submit_button('Guardar configuración');
    echo '</form></div>';
}

/***HERE WE CHARGE THE ARTICLES FROM RSS***/

add_action('admin_post_anf_fetch_articles', 'anf_fetch_articles');

function anf_fetch_articles() {
    // Solo admins
    if (!current_user_can('manage_options')) {
        wp_die('No autorizado');
    }

    $feeds = get_option('anf_feeds', []);
    $include = get_option('anf_keywords_include', []);
    $exclude = get_option('anf_keywords_exclude', []);

    $inserted = 0;

    foreach ($feeds as $feed_url) {
        if (empty($feed_url)) continue;

        $rss = @simplexml_load_file($feed_url);
        if (!$rss) continue;

        foreach ($rss->channel->item as $item) {
            $title = (string) $item->title;
            $description = (string) $item->description;

            $content = $title . ' ' . strip_tags($description);

            $match_include = empty($include) || preg_match('/' . implode('|', array_map('preg_quote', $include)) . '/i', $content);
            $match_exclude = !empty($exclude) && preg_match('/' . implode('|', array_map('preg_quote', $exclude)) . '/i', $content);

            if ($match_include && !$match_exclude) {
                // Verifica si ya existe un post con el mismo título
                $existing = get_page_by_title($title, OBJECT, 'post');
                if (!$existing) {
                    wp_insert_post([
                        'post_title' => $title,
                        'post_content' => $description,
                        'post_status' => 'draft',
                        'post_type' => 'post'
                    ]);
                    $inserted++;
                }
            }
        }
    }

    wp_redirect(admin_url('admin.php?page=auto-news&inserted=' . $inserted));
    exit;
}



add_action('admin_menu', 'anf_add_admin_menu');
add_action('admin_init', 'anf_settings_init');

function anf_settings_init() {

    register_setting('anf_settings', 'anf_rss_feeds');
    register_setting('anf_settings', 'anf_keywords');

    add_settings_section(
        'anf_settings_section',
        'Configuración de Fuentes RSS y Palabras clave',
        null,
        'auto-news'
    );

    add_settings_field(
        'anf_rss_feeds',
        'Feeds RSS (uno por línea)',
        'anf_rss_feeds_render',
        'auto-news',
        'anf_settings_section'    
    
    );
    
    add_settings_field(
        'anf_keywords',
        'Palabras clave (separadas por comas)',
        'anf_keywords_render',
        'auto-news',
        'anf_settings_section'
    );    
    
    
}



function anf_add_admin_menu() {
    add_menu_page(
        'Auto News Fetcher',
        'Auto News',
        'manage_options',
        'auto-news',
        'anf_admin_page'
    );
}


function anf_rss_feeds_render() {
    $feeds = get_option('anf_rss_feeds', '');
    echo '<textarea name="anf_rss_feeds" rows="5" cols="50">' . esc_textarea($feeds) . '</textarea>';
}

function anf_keywords_render() {
    $keywords = get_option('anf_keywords', '');
    echo '<input type="text" name="anf_keywords" value="' . esc_attr($keywords) . '" style="width: 100%;" />';
}


function anf_admin_page() {
    ?>
    <div class="wrap">
        <h1>Auto News Fetcher</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('anf_settings');
            do_settings_sections('auto-news');
            submit_button('Guardar configuración');
            ?>
        </form>

        <hr>

        <h2>Buscar Artículos Nuevos</h2>
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="anf_fetch_articles">
            <button type="submit" class="button button-primary">Buscar y guardar artículos</button>
        </form>

        <?php
        if (isset($_GET['inserted'])) {
            echo '<div class="notice notice-success"><p>' . intval($_GET['inserted']) . ' artículo(s) guardado(s) como borrador.</p></div>';
        }
        ?>
    </div>
    <?php
}

