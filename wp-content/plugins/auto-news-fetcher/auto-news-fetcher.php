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
// Seguridad básica
if (!defined('ABSPATH')) exit; 
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
    xdebug_break();
    $options = get_option('anf_settings', []);
    /***COMENTADO TEMPORALMENTE*****
    $feeds = array_filter(array_map('trim', explode("\n", $options['feeds'] ?? '')));
    *****************************/
    $include_keywords = array_filter(array_map('trim', explode(',', $options['include_keywords'] ?? '')));
    $exclude_keywords = array_filter(array_map('trim', explode(',', $options['exclude_keywords'] ?? '')));

    /* COMENTADO TEMPORALMENTE
    if (empty($feeds)) {
        error_log("[Auto News Fetcher] No hay feeds configurados.");
        echo '<div class="notice notice-error"><p>Error: No hay feeds RSS configurados.</p></div>';
        return;
    }     
     */

    require_once(ABSPATH . WPINC . '/feed.php'); // Cargar la librería de feeds de WP

    $imported_count = 0;
    $errors = [];
    
 // **** INICIO DEL CÓDIGO TEMPORAL PARA PRUEBAS ****
    // Comenta el bucle 'foreach ($feeds as $feed_url)' existente y añade este bloque
    $feed_url_to_test = 'https://www.elnacional.com/feed/'; // <--- ¡AQUÍ ES DONDE ESPECIFICAS EL FEED!
    $max_items_to_test = 1; // <--- Procesar solo el artículo más reciente para pruebas

    try {
        $feed = fetch_feed($feed_url_to_test); // Usa el sistema de caché de WordPress

        if (is_wp_error($feed)) {
            throw new Exception($feed->get_error_message());
        }

        $items = $feed->get_items(0, $max_items_to_test); // Primeros 1 (o más si cambias $max_items_to_test) artículos
        foreach ($items as $item) {
            if (anf_should_import_item($item, $include_keywords, $exclude_keywords)) {
                if (anf_save_news_item($item)) {
                    $imported_count++;
                }
            }
        }
    } catch (Exception $e) {
        $errors[] = "Feed: $feed_url_to_test - Error: " . $e->getMessage();
        error_log("[Auto News Fetcher] " . $e->getMessage());
    }
    // **** FIN DEL CÓDIGO TEMPORAL PARA PRUEBAS ****    

/*    
    
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
*/
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
// Función actualizada para guardar artículo con mejor manejo de imágenes
function anf_save_news_item($item) {
    $title = $item->get_title();
    $guid = $item->get_id();
    $description = $item->get_description();
    $permalink = $item->get_permalink();

/*****LO DESHABILITAMOS TEMPORALMENTE*****    
    // Verificar duplicados (tu código existente)
    $existing_post = new WP_Query([
        'post_type' => 'auto_news',
        'title' => $title,
        'posts_per_page' => 1
    ]);

    if ($existing_post->have_posts() || anf_post_exists_by_guid($guid)) {
        return false;
    }
*********************************************************/
    // Extraer imagen usando la función mejorada
    xdebug_break();
    $image_url = anf_extract_image_from_item($item);
    
    // Crear el post
    $post_data = [
        'post_title'   => $title,
        'post_content' => $description . "\n\n<strong>Fuente:</strong> <a href=\"" . esc_url($permalink) . "\">Enlace original</a>",
        'post_status'  => 'publish',
        'post_type'    => 'auto_news',
        'meta_input'   => [
            'anf_guid' => $guid,
            'anf_source' => $item->get_feed()->get_title(),
            'anf_original_url' => $permalink
        ]
    ];

    $post_id = wp_insert_post($post_data);

    // Intentar importar imagen si existe
    if ($image_url && $post_id && !is_wp_error($post_id)) {
        $attachment_id = anf_import_remote_image($image_url, $post_id);
        if ($attachment_id) {
            error_log("[Auto News Fetcher] Imagen asignada correctamente al post $post_id");
        } else {
            error_log("[Auto News Fetcher] No se pudo asignar imagen al post $post_id - URL: $image_url");
        }
    } else {
        error_log("[Auto News Fetcher] No se encontró imagen para el post: $title");
    }

    return $post_id;
}

// Función de debug para testear extracción de imágenes
function anf_debug_feed_images($feed_url) {
    require_once(ABSPATH . WPINC . '/feed.php');
    
    $feed = fetch_feed($feed_url);
    if (is_wp_error($feed)) {
        return "Error: " . $feed->get_error_message();
    }
    
    $items = $feed->get_items(0, 5);
    $debug_info = [];
    
    foreach ($items as $index => $item) {
        $image_url = anf_extract_image_from_item($item);
        $debug_info[] = [
            'title' => $item->get_title(),
            'image_url' => $image_url,
            'description_preview' => substr(strip_tags($item->get_description()), 0, 100) . '...'
        ];
    }
    
    return $debug_info;
}


// Función mejorada para importar imagen remota
function anf_import_remote_image($image_url, $post_id) {
    if (empty($image_url) || !$post_id) {
        return false;
    }
    
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    
    // Validar que la URL sea una imagen
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $extension = strtolower(pathinfo(parse_url($image_url, PHP_URL_PATH), PATHINFO_EXTENSION));
    
    if (!in_array($extension, $allowed_types)) {
        error_log("[Auto News Fetcher] Tipo de archivo no permitido: $extension para URL: $image_url");
        return false;
    }
    
    // Verificar que la URL responda antes de descargar
    $response = wp_remote_head($image_url, [
        'timeout' => 10,
        'user-agent' => 'WordPress Auto News Fetcher'
    ]);
    
    if (is_wp_error($response)) {
        error_log("[Auto News Fetcher] Error al verificar imagen: " . $response->get_error_message());
        return false;
    }
    
    $content_type = wp_remote_retrieve_header($response, 'content-type');
    if ($content_type && strpos($content_type, 'image/') !== 0) {
        error_log("[Auto News Fetcher] Content-Type no es imagen: $content_type");
        return false;
    }
    
    // Descargar imagen
    $tmp = download_url($image_url, 30); // 30 segundos timeout
    
    if (is_wp_error($tmp)) {
        error_log("[Auto News Fetcher] Error al descargar imagen: " . $tmp->get_error_message());
        return false;
    }
    
    // Validar que el archivo descargado sea realmente una imagen
    $image_info = @getimagesize($tmp);
    if (!$image_info) {
        @unlink($tmp);
        error_log("[Auto News Fetcher] Archivo descargado no es una imagen válida");
        return false;
    }
    
    // Generar nombre único para evitar conflictos
    $filename = 'auto-news-' . $post_id . '-' . time() . '.' . $extension;
    
    $file_array = [
        'name' => $filename,
        'tmp_name' => $tmp
    ];
    
    // Subir a biblioteca de medios
    $attachment_id = media_handle_sideload($file_array, $post_id);
    
    if (is_wp_error($attachment_id)) {
        @unlink($tmp);
        error_log("[Auto News Fetcher] Error al subir imagen: " . $attachment_id->get_error_message());
        return false;
    }
    
    /***********LOG VERIFICA SI NO HAY ERRORES AL BAJAR IMAGENES*******/    
    if ($attachment_id) { // Asegurarse de que el ID no sea 0 o false
        set_post_thumbnail($post_id, $attachment_id);
        error_log("[Auto News Fetcher] DEBUG: Imagen destacada asignada. Post ID: $post_id, Adjunto ID: $attachment_id");
        // ...
    } else {
        error_log("[Auto News Fetcher] DEBUG: media_handle_sideload retornó ID inválido o 0 para URL: " . $image_url);
        return false;
    }    
    /***********FIN DE: LOG VERIFICA SI NO HAY ERRORES AL BAJAR IMAGENE *******/    
    
    // Asignar como imagen destacada
    set_post_thumbnail($post_id, $attachment_id);
    
    // Guardar URL original en meta para referencia
    update_post_meta($post_id, 'anf_original_image_url', $image_url);
    
    
    
    error_log("[Auto News Fetcher] Imagen importada exitosamente: $image_url -> Attachment ID: $attachment_id");
    
    return $attachment_id;
}


function anf_extract_image_from_item($item) {
    xdebug_break();  //THIS IS IMPORTANT FOR USING XDEBUG INSIDE WORDPRESS!
    $image_url = '';
    error_log("[Auto News Fetcher - DEBUG] Procesando item: " . $item->get_title());

    // Obtener todo el contenido posible
    $description = $item->get_description();
    $content = $item->get_content(); // Esto capturará el <content:encoded>
    
    // Priorizamos content:encoded si existe y no está vacío.
    // Si content:encoded tiene la imagen principal, usaremos solo eso para evitar la descripción corta.
    $full_content = !empty($content) ? $content : $description; 
    
    error_log("[Auto News Fetcher - DEBUG] Contenido para regex (primeros 500 chars): " . substr($full_content, 0, 500));

    // --- PRIORIDAD 1: media:content o media:thumbnail (ya lo tienes, déjalo) ---
    // Este código debe estar aquí antes del procesamiento HTML.
    // ... tu código existente para Método 1 y Método 2 (Enclosure)
    if (!empty($image_url)) {
        error_log("[Auto News Fetcher - DEBUG] Encontrada via Media o Enclosure: " . $image_url);
        return anf_validate_and_clean_image_url($image_url, $item->get_feed()->get_permalink());
    }

    // --- PRIORIDAD 2: Buscar en el HTML completo ($full_content) ---
    // Estrategia: Buscar primero las ubicaciones de la imagen principal.

    // Intento A: Buscar la imagen dentro de <noscript> (como en el caso de El Nacional)
    // Usamos 'full_content' que ahora prioriza content:encoded
    if (empty($image_url) && preg_match('/<noscript>\s*<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>\s*<\/noscript>/i', $full_content, $matches)) {
        $image_url = $matches[1];
        error_log("[Auto News Fetcher - DEBUG] Intentando extraer de NOSCRIPT. Resultado: " . (!empty($image_url) ? "OK - " . $image_url : "FALLIDO"));
    }
    
    // Intento B: Si no se encontró en <noscript>, buscar img con data-cfsrc (otra variante de la principal)
    // Esto es útil si la imagen principal usa data-cfsrc y no está en noscript o si SimplePie limpia el noscript.
    if (empty($image_url) && preg_match('/<img[^>]+data-cfsrc=[\'"]([^\'"]+)[\'"][^>]*>/i', $full_content, $matches)) {
        $image_url = $matches[1];
        error_log("[Auto News Fetcher - DEBUG] Intentando extraer de DATA-CFSRC. Resultado: " . (!empty($image_url) ? "OK - " . $image_url : "FALLIDO"));
    }

    // Intento C: Si las anteriores fallaron, buscar la primera img con src (tu regex original)
    // Esto es el último recurso para HTML.
    if (empty($image_url) && preg_match('/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $full_content, $matches)) {
        $image_url = $matches[1];
        error_log("[Auto News Fetcher - DEBUG] Intentando extraer de IMG SRC (fallback). Resultado: " . (!empty($image_url) ? "OK - " . $image_url : "FALLIDO"));
    }

    // --- PRIORIDAD 3: Otros campos RSS (ya lo tienes, déjalo) ---
    // ... tu código existente para Método 4 (si tienes otros campos RSS específicos)

    // Validar y limpiar URL al final
    return anf_validate_and_clean_image_url($image_url, $item->get_feed()->get_permalink());
}


// Nueva función auxiliar para limpieza y validación, para no repetir código
function anf_validate_and_clean_image_url($url, $feed_permalink) {
    if (empty($url)) {
        error_log("[Auto News Fetcher - DEBUG] No se encontró URL de imagen después de todos los métodos.");
        return '';
    }

    $url = trim($url);
    // Convertir URLs relativas a absolutas si es necesario
    if (strpos($url, 'http') !== 0) {
        $parsed_feed = parse_url($feed_permalink);
        // Construir la base URL de manera más robusta
        $base_url = (isset($parsed_feed['scheme']) ? $parsed_feed['scheme'] . '://' : '') . 
                    (isset($parsed_feed['host']) ? $parsed_feed['host'] : '');
        
        // Manejar rutas relativas que empiezan con / (desde la raíz del dominio)
        if (strpos($url, '/') === 0) {
            $url = $base_url . $url;
        } else { // Rutas relativas a la carpeta actual del feed
            $path = isset($parsed_feed['path']) ? dirname($parsed_feed['path']) : '';
            if ($path && $path !== '/') {
                $url = $base_url . $path . '/' . $url;
            } else {
                $url = $base_url . '/' . $url;
            }
        }
        error_log("[Auto News Fetcher - DEBUG] URL relativa convertida a absoluta: " . $url);
    }
    
    // Validar que sea una URL válida
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        error_log("[Auto News Fetcher - DEBUG] URL después de procesamiento NO válida: " . $url);
        return '';
    }
    error_log("[Auto News Fetcher - DEBUG] URL después de procesamiento VÁLIDA: " . $url);
    return $url;
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
        'posts_per_page' => 30,
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
/*
// Agregar al final de tu archivo, temporalmente
add_action('wp_ajax_test_feed_images', function() {
    $feed_url = 'https://feeds.as.com/mrss-s/pages/as/site/as.com/section/futbol/subsection/primera/';
    $debug = anf_debug_feed_images($feed_url);
    wp_die('<pre>' . print_r($debug, true) . '</pre>');
});
 * 
 */