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

// Registrar el template personalizado
add_filter('theme_page_templates', function($templates) {
    $templates['templates/template-noticias-automaticas.php'] = 'Plantilla Noticias Automáticas';
    return $templates;
});
/*
// Cargar el template desde el plugin
add_filter('template_include', function($template) {
    global $post;
    
    if (is_page() && 
        get_page_template_slug($post->ID) === 'templates/template-noticias-automaticas.php' && 
        file_exists(plugin_dir_path(__FILE__) . 'templates/template-noticias-automaticas.php'))
    {
        return plugin_dir_path(__FILE__) . 'templates/template-noticias-automaticas.php';
    }
    
    return $template;
});
*/

// Cargar template
add_filter('template_include', function($template) {
    if (is_page() && get_page_template_slug() === 'templates/template-noticias-automaticas.php') {
        $new_template = plugin_dir_path(__FILE__) . 'templates/template-noticias-automaticas.php';
        if (file_exists($new_template)) {
            return $new_template;
        }
    }
    return $template;
});




// Registrar CPT con más opciones
add_action('init', function() {
    register_post_type('auto_news', [
        'public' => true,
        'label'  => 'Noticias Automáticas',
        'labels' => [
            'name'          => 'Noticias Automáticas',
            'singular_name' => 'Noticia Automática',
            'add_new_item'  => 'Añadir Nueva Noticia'
        ],
        'menu_icon'   => 'dashicons-rss',
        'supports'    => ['title', 'editor', 'thumbnail'],
        'has_archive' => true, // Para poder crear página de archivo
        'rewrite'     => ['slug' => 'noticias-automaticas']
    ]);
});

// Flush rules al activar el plugin (solo una vez)
register_activation_hook(__FILE__, function() {
    flush_rewrite_rules();
});


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
/*
add_action('init', function() {
    register_post_type('auto_news', [
        'public' => true,
        'label'  => 'Noticias Automáticas',
        'supports' => ['title', 'editor', 'thumbnail'],
        'menu_icon' => 'dashicons-rss'
    ]);
});
*/
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
    $description = $item->get_description();
    $permalink = $item->get_permalink();

    // Evitar duplicados
    /*if (get_page_by_title($title, OBJECT, 'auto_news') || anf_post_exists_by_guid($guid)) {*/
    
    $existing_post = new WP_Query([
    'post_type' => 'auto_news',
    'title' => $title,
    'posts_per_page' => 1
    ]);

    if ($existing_post->have_posts() || anf_post_exists_by_guid($guid)) {
        $query = new WP_Query([
            'post_type' => 'auto_news',
            'meta_query' => [
                [
                    'key' => 'anf_guid',
                    'value' => $guid
                ]
            ],
            'posts_per_page' => 1,
            'fields' => 'ids'
        ]);
    
    return $query->have_posts();        
        //return false;
    }

    // 1. Extraer imagen del contenido (si existe)
    $image_url = '';
    /*
    if ($item->get_enclosure() && $item->get_enclosure()->get_thumbnail()) {
        $image_url = $item->get_enclosure()->get_thumbnail();
    } else {
        // Alternativa: Buscar imagen en el contenido HTML
        preg_match('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $description, $matches);
        $image_url = $matches[1] ?? '';
    }
     */
    // Alternativa para feeds con media:content
    if ($item->get_enclosure() && $item->get_enclosure()->link) {
        $image_url = $item->get_enclosure()->link;
    } else {
        // Alternativa: Buscar imagen en el contenido HTML
        preg_match('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $description, $matches);
        $image_url = $matches[1] ?? '';
    }    

    // 2. Crear array de datos del post
    $post_data = [
        'post_title'   => $title,
        'post_content' => $description . "\n\n<strong>Fuente:</strong> <a href=\"" . esc_url($permalink) . "\">Enlace original</a>",
        'post_status'  => 'publish',
        'post_type'    => 'auto_news',
        'meta_input'   => [
            'anf_guid' => $guid,
            'anf_source' => $item->get_feed()->get_title(),
            'anf_image_url' => $image_url // Guardamos la URL temporalmente
        ]
    ];

    $post_id = wp_insert_post($post_data);

    // 3. Si hay imagen, descargarla y asignarla como featured
    if ($image_url && $post_id && !is_wp_error($post_id)) {
        anf_import_remote_image($image_url, $post_id);
    }

    return $post_id;
}


function anf_import_remote_image($image_url, $post_id) {
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    // 1. Descargar imagen temporalmente
    $tmp = download_url($image_url);
    
    if (is_wp_error($tmp)) {
        error_log("[Auto News Fetcher] Error al descargar imagen: " . $tmp->get_error_message());
        return false;
    }

    // 2. Preparar datos del archivo
    $file_array = [
        'name' => basename($image_url),
        'tmp_name' => $tmp
    ];

    // 3. Subir a la biblioteca de medios
    $attachment_id = media_handle_sideload($file_array, $post_id);

    if (is_wp_error($attachment_id)) {
        @unlink($tmp);
        error_log("[Auto News Fetcher] Error al subir imagen: " . $attachment_id->get_error_message());
        return false;
    }

    // 4. Asignar como imagen destacada
    set_post_thumbnail($post_id, $attachment_id);
    
    // 5. Limpiar URL temporal del meta
    delete_post_meta($post_id, 'anf_image_url');
    
    return $attachment_id;
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


/******PUNTO 5 ************/
// Shortcode para mostrar noticias
add_shortcode('mostrar_noticias_automaticas', function($atts) {
    ob_start();
    
    $args = [
        'post_type'      => 'auto_news',
        'posts_per_page' => 5,
        'post_status'    => 'publish'
    ];
    
    $noticias = new WP_Query($args);
    
    if ($noticias->have_posts()) :
        echo '<div class="noticias-automaticas">';
        while ($noticias->have_posts()) : $noticias->the_post();
            echo '<article>';
            the_title('<h3>', '</h3>');
            the_content();
            $fuente = get_post_meta(get_the_ID(), 'anf_guid', true);
            if ($fuente) {
                echo '<a href="' . esc_url($fuente) . '" target="_blank">Ver fuente original</a>';
            }
            echo '</article>';
        endwhile;
        echo '</div>';
    else :
        echo '<p>No hay noticias disponibles.</p>';
    endif;
    
    wp_reset_postdata();
    return ob_get_clean();
});

/**********PUNTO 6************/

// Registrar el evento cron al activar el plugin
register_activation_hook(__FILE__, 'anf_activate_cron');
function anf_activate_cron() {
    if (!wp_next_scheduled('anf_daily_fetch')) {
        wp_schedule_event(time(), 'daily', 'anf_daily_fetch');
    }
}

// Eliminar el evento al desactivar el plugin
register_deactivation_hook(__FILE__, 'anf_deactivate_cron');
function anf_deactivate_cron() {
    wp_clear_scheduled_hook('anf_daily_fetch');
}

// Hook para la acción cron
add_action('anf_daily_fetch', 'anf_execute_auto_fetch');

function anf_execute_auto_fetch() {
    $options = get_option('anf_settings');
    $errors = [];
    $imported_count = 0;
    
    if (empty($options['feeds'])) {
        $errors[] = "No hay feeds RSS configurados";
        error_log("[Auto News Fetcher] " . end($errors));
        anf_send_notification(0, $errors);
        return;
    }
    
    // Ejecutar importación (capturar resultados)
    ob_start();
    $imported_count = anf_fetch_and_save_articles();
    $output = ob_get_clean();
    
    // Enviar email
    anf_send_notification($imported_count, $errors);
    
    return $imported_count;
}


/***************PUNTO 6.3.********************************************/

// Función para enviar el email
function anf_send_notification($imported_count, $errors = []) {
    $to = get_option('admin_email'); // Email del admin de WordPress
    $subject = 'Resumen de importación automática de noticias';
    
    $message = "
        <h1>Resumen de importación automática</h1>
        <p><strong>Fecha:</strong> " . current_time('mysql') . "</p>
        <p><strong>Noticias importadas:</strong> " . $imported_count . "</p>
    ";
    
    if (!empty($errors)) {
        $message .= "<h2>Errores:</h2><ul>";
        foreach ($errors as $error) {
            $message .= "<li>" . esc_html($error) . "</li>";
        }
        $message .= "</ul>";
    }
    
    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: Auto News Fetcher <noreply@' . $_SERVER['HTTP_HOST'] . '>'
    ];
    
    wp_mail($to, $subject, $message, $headers);
}
/*********ESTA FUNCION DE NOTIFICACIONES NO LA VAMOS A IMPLEMENTAR TODAVIA*********
// Modificar la función de fetch automático para incluir notificación
function anf_execute_auto_fetch() {
    $options = get_option('anf_settings');
    $errors = [];
    $imported_count = 0;
    
    if (empty($options['feeds'])) {
        $errors[] = "No hay feeds RSS configurados";
        error_log("[Auto News Fetcher] " . end($errors));
        anf_send_notification(0, $errors);
        return;
    }
    
    // Ejecutar importación (capturar resultados)
    ob_start();
    $imported_count = anf_fetch_and_save_articles();
    $output = ob_get_clean();
    
    // Enviar email
    anf_send_notification($imported_count, $errors);
    
    return $imported_count;
}

// Actualizar la función original para que retorne el conteo
function anf_fetch_and_save_articles() {
    $options = get_option('anf_settings', []);
    $feeds = array_filter(array_map('trim', explode("\n", $options['feeds'] ?? '')));
    $imported_count = 0;
    
    foreach ($feeds as $feed_url) {
        // ... (código existente)
        if (anf_save_news_item($item)) {
            $imported_count++; //  Contar posts importados
        }
    }
    
    return $imported_count; // Retornar el total
}

*************************************************************************************/