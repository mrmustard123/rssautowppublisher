<?php
/**
 * Template Name: Noticias Automáticas
 * Template Post Type: page
 */
if (!defined('ABSPATH')) exit; // Seguridad

get_header(); 

// 1. Obtener parámetro de búsqueda
$filtro = isset($_GET['buscar']) ? sanitize_text_field($_GET['buscar']) : '';

// 2. Configurar query para noticias
$args = [
    'post_type' => 'auto_news',
    'posts_per_page' => 10,
    'post_status' => 'publish',
    's' => $filtro // Búsqueda
];

$noticias = new WP_Query($args);
?>

<div class="wrap">
    <h1>Noticias Automáticas</h1>
    
    <!-- Buscador -->
    <form method="get" class="anf-search-form">
        <input type="text" name="buscar" placeholder="Buscar noticias..." 
               value="<?php echo esc_attr($filtro); ?>">
        <button type="submit">Buscar</button>
    </form>
    
    <!-- Lista de noticias -->
    <?php if ($noticias->have_posts()) : ?>
        <div class="noticias-list">
            <?php while ($noticias->have_posts()) : $noticias->the_post(); ?>
                <article class="noticia-item">
                    <?php if (has_post_thumbnail()) : ?>
                        <div class="noticia-imagen">
                            <?php the_post_thumbnail('medium'); ?>
                        </div>
                    <?php endif; ?>

                    <h2><?php the_title(); ?></h2>
                    <div class="noticia-content">
                        <?php the_content(); ?>
                    </div>
                    <?php $fuente = get_post_meta(get_the_ID(), 'anf_guid', true); ?>
                    <?php if ($fuente) : ?>
                        <a href="<?php echo esc_url($fuente); ?>" class="fuente-link" target="_blank">Fuente original</a>
                    <?php endif; ?>
                </article>
            <?php endwhile; ?>
        </div>
        
        <?php wp_reset_postdata(); ?>
    <?php else : ?>
        <p class="no-noticias">No se encontraron noticias.</p>
    <?php endif; ?>
</div>

<?php
get_footer();
?>